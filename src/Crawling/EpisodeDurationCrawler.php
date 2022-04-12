<?php

namespace App\Crawling;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class EpisodeDurationCrawler implements EpisodeFileCrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileDownloader $fileDownloader,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode, \DateTime $ifModifiedSince = null): ?\DateTime
    {
        if (!$episode->getRecordingUri()) {
            $this->logger->warning(sprintf('Recording URI for episode %s is empty.', $episode->getCode()));

            return null;
        }

        $path = sprintf('%s/episodes/%s.mp3', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        $lastModifiedAt = $this->fileDownloader->download($episode->getRecordingUri(), $path, $ifModifiedSince);

        // The file is not downloaded if there are no changes
        if ($lastModifiedAt !== $ifModifiedSince) {
            $duration = $this->fetchRecordingDuration($path);

            if ($duration !== $episode->getDuration()) {
                $episode->setDuration($duration);

                $this->entityManager->persist($episode);
            }

            (new Filesystem())->remove($path);
        }

        return $lastModifiedAt;
    }

    private function fetchRecordingDuration(string $path): int
    {
        $command = 'ffmpeg -i $RECORDING 2>&1 | grep "Duration"';
        $process = Process::fromShellCommandline($command);

        $process->mustRun(null, [
            'RECORDING' => $path,
        ]);

        $output = $process->getOutput();

        preg_match("/Duration: (\d+):(\d+):(\d+)/", $output, $matches);
        list(, $hours, $minutes, $seconds) = $matches;

        return $seconds + ($minutes * 60) + ($hours * 60 * 60);
    }
}
