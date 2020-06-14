<?php

namespace App\Crawling;

use App\Entity\BatSignal;
use App\Entity\Episode;
use App\Message\MatchEpisodeChatMessages;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;

class EpisodeRecordingTimeMatcher
{
    use LoggerAwareTrait;

    private $entityManager;
    private $messenger;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messenger)
    {
        $this->entityManager = $entityManager;
        $this->messenger = $messenger;
        $this->logger = new NullLogger();
    }

    public function match(Episode $episode): void
    {
        $signalRepository = $this->entityManager->getRepository(BatSignal::class);

        if (!$episode->getDuration()) {
            $this->logger->warning('Episode duration must be available to match the recording time.');

            return;
        }

        $signal = $signalRepository->findOneByEpisode($episode);

        if (!$signal) {
            $this->logger->warning('Unable to find a matching bat signal.');

            return;
        }

        $liveFiles = $this->getLivestreamRecordings($signal);

        if ($liveFiles->count() === 0) {
            $this->logger->warning('No livestream recordings found that match the given bat signal.');

            return;
        }

        $this->splitRecording($episode);

        $recordedAt = $this->matchRecordings($episode, $signal);

        $this->logger->info(sprintf('Matched episode recording time: %s', $recordedAt->format('Y-m-d H:i:s')));

        $episode->setRecordedAt($recordedAt);

        $this->entityManager->persist($episode);

        $matchChatMessagesMessage = new MatchEpisodeChatMessages($episode->getCode());
        $this->messenger->dispatch($matchChatMessagesMessage);
    }

    private function matchRecordings(Episode $episode, BatSignal $signal): \DateTime
    {
        $sourcePath = sprintf('%s/episode_parts', $_SERVER['APP_STORAGE_PATH']);

        $sourceFiles = Finder::create()
            ->files()
            ->in($sourcePath)
            ->name(sprintf('%s_*.mp3', $episode->getCode()))
        ;

        $liveFiles = $this->getLivestreamRecordings($signal);

        $recordingMatrix = [];

        /** @var \SplFileInfo $liveFile */
        foreach ($liveFiles as $liveFile) {
            $timestamp = substr($liveFile->getFilename(), strlen('recording_'), 14);

            /** @var \SplFileInfo $sourceFile */
            foreach ($sourceFiles as $sourceFile) {
                $recordedAt = new \DateTime($timestamp);

                preg_match("/_(\d+)./", $sourceFile->getFilename(), $matches);
                list(, $offset) = $matches;

                $command = 'audio-offset-finder --find-offset-of "$LIVE_FILE" --within "$SOURCE_FILE"';
                $process = Process::fromShellCommandline($command);

                $process->setTimeout(600);

                $process->run(null, [
                    'LIVE_FILE' => $liveFile->getPathname(),
                    'SOURCE_FILE' => $sourceFile->getPathname(),
                ]);

                preg_match("/Offset: (\d+)/", $process->getOutput(), $matches);
                list(, $matchedOffset) = $matches;
                preg_match("/score: (\d+)/", $process->getOutput(), $matches);
                list(, $matchedScore) = $matches;

                $recordingOffset = $offset + $matchedOffset;
                $episodeRecordedAt = $recordedAt->sub(new \DateInterval('PT' . $recordingOffset . 'S'));

                $episodeRecordedAtKey = $episodeRecordedAt->format('YmdHis');

                if (!isset($recordingMatrix[$episodeRecordedAtKey])) {
                    $recordingMatrix[$episodeRecordedAtKey] = [];
                }

                $recordingMatrix[$episodeRecordedAtKey][] = $matchedScore;

                $this->logger->debug(sprintf('Matched live recording "%s" to episode offset %s-%s to "%s" with a score of %s.',
                    $timestamp,
                    $offset,
                    $offset + 600,
                    $episodeRecordedAt->format('Y-m-d H:i:s'),
                    $matchedScore
                ));
            }
        }

        // Optimize recording matrix
        ksort($recordingMatrix);

        foreach ($recordingMatrix as $key => $scores) {
            foreach ($recordingMatrix as $matchKey => $matchScores) {
                // Found similar timestamps
                if ($matchKey > $key && $matchKey < ($key + 5)) {
                    // The latter has more scores
                    if (count($matchScores) > count($scores)) {
                        unset($recordingMatrix[$key]);
                        $recordingMatrix[$matchKey] = array_merge($scores, $matchScores);

                        continue 2;
                    }

                    // The first one has more or equal scores
                    unset($recordingMatrix[$matchKey]);
                    $recordingMatrix[$key] = array_merge($scores, $matchScores);
                }
            }
        }

        // Sort by top match
        uasort($recordingMatrix, function($a, $b) {
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

        $listing = [];
        foreach ($recordingMatrix as $key => $matches) {
            $listing[] = sprintf('%s: %s', $key, implode(', ', $matches));
        }

        $this->logger->info('Recording matrix dump: ' . "\n" . implode($listing, "\n"));

        return new \DateTime(array_key_first($recordingMatrix));
    }

    private function splitRecording(Episode $episode): void
    {
        $sourcePath = sprintf('%s/episode_parts', $_SERVER['APP_STORAGE_PATH']);

        if (!is_dir($sourcePath)) {
            $filesystem = new Filesystem();
            $filesystem->mkdir($sourcePath);
        }

        // Clean up directory
        $sourceFiles = Finder::create()
            ->files()
            ->in($sourcePath)
        ;

        foreach ($sourceFiles as $sourceFile) {
            unlink($sourceFile);
        }

        $sourcePath = sprintf('%s/episode_recordings/%s.mp3', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        $targetPathPrefix = sprintf('%s/episode_parts/%s_', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

        $command = 'bin/scripts/split-recording.bash "$SOURCE_PATH" "$TARGET_PATH"';
        $process = Process::fromShellCommandline($command);

        $process->setTimeout(1800);

        $returnCode = $process->run(null, [
            'SOURCE_PATH' => $sourcePath,
            'TARGET_PATH' => $targetPathPrefix,
        ]);

        if ($returnCode > 0) {
            $this->logger->critical('An error occurred while splitting the recording.');

            throw new \Exception('Failed to split recording');
        }
    }

    private function getLivestreamRecordings(BatSignal $signal): Finder
    {
        $livePath = sprintf('%s/livestream_recordings', $_SERVER['APP_STORAGE_PATH']);

        return Finder::create()
            ->files()
            ->in($livePath)
            ->name('recording_*.asf')
            ->filter(function(\SplFileInfo $file) use ($signal) {
                $timestamp = substr($file->getFilename(), strlen('recording_'), 14);
                $recordedAt = new \DateTime($timestamp);

                // Filter out files that are recorded before the bat signal
                if ($recordedAt < $signal->getDeployedAt()) {
                    return false;
                }

                $recordedBefore = (new \DateTime($signal->getDeployedAt()->format('YmdHis')))->add(new \DateInterval('PT3H'));

                // Filter out files that are recorded more than 3 hours after the bat signal
                if ($recordedAt > $recordedBefore) {
                    return false;
                }

                return true;
            })
            ->sortByName()
        ;
    }
}
