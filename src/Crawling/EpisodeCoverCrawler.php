<?php

namespace App\Crawling;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Service\FilterService;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\MessageBusInterface;

class EpisodeCoverCrawler implements EpisodeFileCrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messenger,
        private FileDownloader $fileDownloader,
        private ?FilterManager $filterManager = null,
        private ?FilterService $filterService = null,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode, \DateTime $ifModifiedSince = null): \DateTime
    {
        $path = sprintf('%s/covers/%s.png', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        $lastModifiedAt = $this->fileDownloader->download($episode->getCoverUri(), $path, $ifModifiedSince);

        if ($path !== $episode->getCoverPath()) {
            $episode->setCoverPath($path);

            $this->entityManager->persist($episode);
        }

        $this->resolveCoverCache($episode->getCode());

        return $lastModifiedAt;
    }

    private function resolveCoverCache(string $code): void
    {
        if (!$this->filterManager) {
            // todo test resolving of cache in unit tests
            return;
        }

        $filters = array_keys($this->filterManager->getFilterConfiguration()->all());

        foreach ($filters as $filter) {
            $this->filterService->bustCache("${code}.png", $filter);
            $this->filterService->getUrlOfFilteredImage("${code}.png", $filter);
        }
    }
}
