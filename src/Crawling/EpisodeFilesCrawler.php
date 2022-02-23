<?php

namespace App\Crawling;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Service\FilterService;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class EpisodeFilesCrawler
{
    use LoggerAwareTrait;

    private $entityManager;
    private $filterManager;
    private $filterService;

    public function __construct(EntityManagerInterface $entityManager, FilterManager $filterManager, FilterService $filterService)
    {
        $this->entityManager = $entityManager;
        $this->filterManager = $filterManager;
        $this->filterService = $filterService;
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode): void
    {
        $recordingPath = sprintf('%s/episode_recordings/%s.mp3', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        $coverPath = sprintf('%s/episode_covers/%s.png', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

        if (!is_dir(dirname($recordingPath)) || !is_dir(dirname($coverPath))) {
            $filesystem = new Filesystem();
            $filesystem->mkdir([
                dirname($recordingPath),
                dirname($coverPath),
            ]);
        }

        if ($this->putContents($episode->getRecordingUri(), $recordingPath)) {
            $recordingDuration = $this->fetchRecordingDuration($recordingPath);

            if (!$recordingDuration) {
                $this->logger->warning("Unable to retrieve recording duration of file ${recordingPath}.");
            } else {
                $episode->setDuration($recordingDuration);
            }
        }

        if ($episode->getCoverUri()) {
            if ($this->putContents($episode->getCoverUri(), $coverPath)) {
                $episode->setCover(true);
                $this->resolveCoverCache($episode->getCode());
            }
        }

        $this->entityManager->persist($episode);
    }

    public function crawlTranscript(Episode $episode): void
    {
        if (!$episode->getTranscriptUri()) {
            return;
        }

        if ($this->putContents($episode->getTranscriptUri(), $episode->getTranscriptPath())) {
            $episode->setTranscript(true);

            $this->entityManager->persist($episode);
        }
    }

    private function putContents(string $source, string $target): bool
    {
        $this->logger->debug("Downloading $source");

        if (!is_dir($targetDirectory = dirname($target))) {
            (new Filesystem())->mkdir($targetDirectory);
        }

        try {
            $stream = fopen($source, 'r');
        } catch (\Exception $exception) {
            $this->logger->warning("Failed to download $source: " . $exception->getMessage());

            return false;
        }

        file_put_contents($target, $stream);

        return true;
    }

    private function fetchRecordingDuration(string $path): ?int
    {
        $command = 'ffmpeg -i $RECORDING 2>&1 | grep "Duration"';
        $process = Process::fromShellCommandline($command);

        $process->run(null, [
            'RECORDING' => $path,
        ]);

        if (!$process->isSuccessful()) {
            $this->logger->warning("An error occurred while matching the duration of file: $path");

            return null;
        }

        $output = $process->getOutput();

        preg_match("/Duration: (\d+):(\d+):(\d+)/", $output, $matches);
        list(, $hours, $minutes, $seconds) = $matches;

        return $seconds + ($minutes * 60) + ($hours * 60 * 60);
    }

    private function resolveCoverCache(string $code): void
    {
        $filters = array_keys($this->filterManager->getFilterConfiguration()->all());

        foreach ($filters as $filter) {
            $this->filterService->bustCache("${code}.png", $filter);
            $this->filterService->getUrlOfFilteredImage("${code}.png", $filter);
        }
    }
}
