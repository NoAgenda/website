<?php

namespace App\Crawling;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class EpisodeChaptersCrawler implements EpisodeFileCrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FileDownloader $fileDownloader,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode, \DateTime $ifModifiedSince = null): ?\DateTime
    {
        if (!$episode->getChaptersUri()) {
            $this->logger->warning(sprintf('Chapters URI for episode %s is empty.', $episode->getCode()));

            return null;
        }

        $path = sprintf('%s/chapters/%s.json', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        $lastModifiedAt = $this->fileDownloader->download($episode->getChaptersUri(), $path, $ifModifiedSince);

        if ($path !== $episode->getChaptersPath()) {
            $episode->setChaptersPath($path);

            $this->entityManager->persist($episode);
        }

        return $lastModifiedAt;
    }
}
