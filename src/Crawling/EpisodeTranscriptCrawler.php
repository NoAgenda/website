<?php

namespace App\Crawling;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\MessageBusInterface;

class EpisodeTranscriptCrawler implements EpisodeFileCrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messenger,
        private FileDownloader $fileDownloader,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode, \DateTime $ifModifiedSince = null): \DateTime
    {
        $path = sprintf('%s/transcripts/%s.%s', $_SERVER['APP_STORAGE_PATH'], $episode->getCode(), $episode->getTranscriptType());
        $lastModifiedAt = $this->fileDownloader->download($episode->getTranscriptUri(), $path, $ifModifiedSince);

        if ($path !== $episode->getTranscriptPath()) {
            $episode->setTranscriptPath($path);

            $this->entityManager->persist($episode);
        }

        return $lastModifiedAt;
    }
}
