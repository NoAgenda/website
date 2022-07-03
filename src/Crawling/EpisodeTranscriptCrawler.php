<?php

namespace App\Crawling;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class EpisodeTranscriptCrawler implements EpisodeFileCrawlerInterface
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
        if (!$episode->getTranscriptUri()) {
            $this->logger->warning(sprintf('Transcript URI for episode %s is empty.', $episode->getCode()));

            return null;
        }

        $path = sprintf('%s/transcripts/%s.%s', $_SERVER['APP_STORAGE_PATH'], $episode->getCode(), $episode->getTranscriptType());
        $lastModifiedAt = $this->fileDownloader->download($episode->getTranscriptUri(), $path, $ifModifiedSince);

        if ($path !== $episode->getTranscriptPath()) {
            $episode->setTranscriptPath($path);

            $this->entityManager->persist($episode);
        }

        return $lastModifiedAt;
    }
}
