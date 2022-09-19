<?php

namespace App\Crawling;

use App\Entity\BatSignal;
use App\Entity\Episode;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class EpisodeRecordingTimeMatcher implements EpisodeCrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BatSignalRepository $batSignalRepository,
        private FileDownloader $fileDownloader,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode): void
    {
        if (!$episode->getDuration()) {
            $this->logger->warning(sprintf('Unable to match recording time for episode %s because its duration is unknown.', $episode->getCode()));

            return;
        }

        if (!$signal = $this->batSignalRepository->findOneByEpisode($episode)) {
            $this->logger->warning(sprintf('Unable to find a bat signal matching episode %s.', $episode->getCode()));

            return;
        }

        $this->logger->info(sprintf('Found matching bat signal published at %s.', $signal->getDeployedAt()->format('Y-m-d H:i:s')));

        if (!$recordings = $this->getLivestreamRecordings($episode, $signal)) {
            $this->logger->warning(sprintf('No livestream recordings found matching episode %s.', $episode->getCode()));

            return;
        }

        $this->logger->debug('Splitting episode recording into parts');

        $parts = $this->splitEpisodeRecording($episode);

        $this->logger->debug('Building recording matrix');

        if (!$matrix = $this->buildMatrix($recordings, $parts)) {
            $this->logger->warning(sprintf('Unable to build a recording time matrix for episode %s.', $episode->getCode()));

            return;
        }

        $recordedAt = new \DateTime(array_key_first($matrix));

        $this->logger->info(sprintf('Matched recording time for episode %s: %s', $episode->getCode(), $recordedAt->format('Y-m-d H:i:s')));
        $this->logger->debug(sprintf("Recording matrix dump:\n%s", self::printMatrix($matrix)));

        $episode->setRecordedAt($recordedAt);
        $episode->setRecordingTimeMatrix($matrix);

        $this->entityManager->persist($episode);
    }

    private function buildMatrix(Finder $recordings, Finder $parts): ?array
    {
        $matrix = [];

        /** @var \SplFileInfo $recording */
        foreach ($recordings as $recording) {
            $timestamp = substr($recording->getFilename(), strlen('recording_'), 14);

            /** @var \SplFileInfo $part */
            foreach ($parts as $part) {
                preg_match("/_(\d+)./", $part->getFilename(), $matches);
                list(, $offset) = $matches;

                $command = 'audio-offset-finder --not-generate "$PART" "$RECORDING"';
                $process = Process::fromShellCommandline($command)
                    ->setTimeout(600);

                $process->mustRun(null, [
                    'PART' => $part->getPathname(),
                    'RECORDING' => $recording->getPathname(),
                ]);

                preg_match("/The offset calculated is: (\S+)/", $process->getOutput(), $matches);
                $matchedOffset = $matches[1] ?? null;
                preg_match("/The score is: (\S+)/", $process->getOutput(), $matches);
                $matchedScore = $matches[1] ?? null;

                if (null === $matchedOffset || null == $matchedScore) {
                    $this->logger->notice(sprintf('Failed to parse recording time matcher output: %s', $process->getOutput()));

                    continue;
                }

                $matchedOffset = floor($matchedOffset);
                $matchedScore = floor($matchedScore);

                if ($matchedScore < 8) {
                    continue;
                }

                $recordingOffset = $offset + $matchedOffset;
                $episodeRecordedAt = (new \DateTime($timestamp))->sub(new \DateInterval("PT${recordingOffset}S"));

                $episodeRecordedAtKey = $episodeRecordedAt->format('YmdHis');

                if (!isset($matrix[$episodeRecordedAtKey])) {
                    $matrix[$episodeRecordedAtKey] = [];
                }

                $matrix[$episodeRecordedAtKey][] = $matchedScore;

                $this->logger->debug(sprintf('Matched live recording "%s" to episode offset %s-%s to "%s" with a score of %s.',
                    $timestamp,
                    $offset,
                    $offset + 600,
                    $episodeRecordedAt->format('Y-m-d H:i:s'),
                    $matchedScore,
                ));
            }
        }

        if (!count($matrix)) {
            return null;
        }

        ksort($matrix);

        // Group similar timestamps
        foreach ($matrix as $key => $scores) {
            foreach ($matrix as $matchKey => $matchScores) {
                if ($matchKey > $key && $matchKey < ($key + 5)) {
                    if (count($matchScores) > count($scores)) {
                        unset($matrix[$key]);
                        $matrix[$matchKey] = array_merge($scores, $matchScores);
                    } else {
                        unset($matrix[$matchKey]);
                        $matrix[$key] = array_merge($scores, $matchScores);
                    }
                }
            }
        }

        // Sort by top match
        uasort($matrix, function($a, $b) {
            // A has more matches
            if (count($a) > count($b)) {
                return -1;
            }

            // B has more matches
            if (count($a) < count($b)) {
                return 1;
            }

            $averageA = array_sum($a) / count($a);
            $averageB = array_sum($b) / count($b);

            if ($averageA == $averageB) {
                return 0;
            }

            return ($averageA > $averageB) ? -1 : 1;
        });

        return $matrix;
    }

    private function findLivestreamRecordings(Episode $episode, BatSignal $signal): bool
    {
        $episodeLivestreamRecordingsPath = sprintf('%s/episode_livestream_recordings/%s', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        $livestreamRecordingsPath = sprintf('%s/livestream_recordings', $_SERVER['APP_STORAGE_PATH']);

        if (file_exists($episodeLivestreamRecordingsPath)) {
            return true;
        }

        // Approximate recording time
        $recordingPadding = $episode->getDuration();
        $recordingPadding += 60 * 60; // 60 minutes

        $recordedAfter = $signal->getDeployedAt();
        $recordedBefore = (new \DateTime('@'.$recordedAfter->getTimestamp()))
            ->add(new \DateInterval(sprintf('PT%dS', $recordingPadding)));

        $files = (new Finder())
            ->files()
            ->in($livestreamRecordingsPath)
            ->name('recording_*')
            ->filter(function(\SplFileInfo $file) use ($recordedAfter, $recordedBefore) {
                $timestamp = substr($file->getFilename(), strlen('recording_'), 14);
                $recordedAt = new \DateTime($timestamp);

                return $recordedAt > $recordedAfter && $recordedAt < $recordedBefore;
            });

        if (!count($files)) {
            return false;
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($episodeLivestreamRecordingsPath);

        foreach ($files as $file) {
            $filesystem->copy($file->getPathname(), $episodeLivestreamRecordingsPath . '/' . $file->getFilename());
        }

        return true;
    }

    private function getLivestreamRecordings(Episode $episode, BatSignal $signal): ?Finder
    {
        $episodeLivestreamRecordingsPath = sprintf('%s/episode_livestream_recordings/%s', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

        if (!$this->findLivestreamRecordings($episode, $signal)) {
            return null;
        }

        $recordedAfter = $signal->getDeployedAt();
        $recordedBefore = (new \DateTime('@'.$recordedAfter->getTimestamp()))
            ->add(new \DateInterval('PT2H'));

        return (new Finder())
            ->files()
            ->in($episodeLivestreamRecordingsPath)
            ->name('recording_*.asf')
            ->filter(function(\SplFileInfo $file) use ($recordedAfter, $recordedBefore)  {
                $timestamp = substr($file->getFilename(), strlen('recording_'), 14);
                $recordedAt = new \DateTime($timestamp);

                return $recordedAt > $recordedAfter && $recordedAt < $recordedBefore;
            })
            ->sortByName();
    }

    private function splitEpisodeRecording(Episode $episode): Finder
    {
        $sourcePath = sprintf('%s/episodes/%s.mp3', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        $outputPath = sprintf('%s/episode_parts', $_SERVER['APP_STORAGE_PATH']);
        $outputPrefix = sprintf('%s/%s_', $outputPath, $episode->getCode());

        $this->fileDownloader->download($episode->getRecordingUri(), $sourcePath);

        // Prepare output directory, fully clear it because the matcher is still prone to errors
        $filesystem = new Filesystem();
        $filesystem->mkdir($outputPath);
        $filesystem->remove((new Finder())
            ->files()
            ->in($outputPath)
        );

        foreach ([0, 300, 600, 900, 1200] as $offset) {
            $command = 'ffmpeg -ss $OFFSET -t 600 -i "$SOURCE_PATH" "$OUTPUT_PREFIX$OFFSET".mp3';
            $process = Process::fromShellCommandline($command)
                ->setTimeout(300);

            $process->mustRun(null, [
                'OFFSET' => $offset,
                'SOURCE_PATH' => $sourcePath,
                'OUTPUT_PREFIX' => $outputPrefix,
            ]);
        }

        return (new Finder())
            ->files()
            ->in($outputPath)
            ->name(sprintf('%s_*.mp3', $episode->getCode()));
    }

    public static function printMatrix(array $matrix): string
    {
        $listing = [];
        foreach ($matrix as $key => $matches) {
            $listing[] = sprintf('%s: %s', $key, implode(', ', $matches));
        }

        return implode("\n", $listing);
    }
}
