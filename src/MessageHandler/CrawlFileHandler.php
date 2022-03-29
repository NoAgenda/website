<?php

namespace App\MessageHandler;

use App\Crawling\EpisodeFileCrawlerInterface;
use App\Crawling\FileDownloader;
use App\Message\CrawlFile;
use App\Repository\EpisodeRepository;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlFileHandler implements MessageHandlerInterface
{
    public function __construct(
        private ContainerInterface $crawlers,
        private EpisodeRepository $episodeRepository,
        private FileDownloader $fileDownloader,
    ) {}

    public function __invoke(CrawlFile $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($message->episodeCode);

        /** @var EpisodeFileCrawlerInterface $crawler */
        $crawler = $this->crawlers->get($message->crawler);
        $lastModifiedAt = $crawler->crawl($episode, $message->lastModifiedAt);

        $this->fileDownloader->updateSchedule($message->crawler, $episode, $lastModifiedAt, $message->initializedAt);
    }
}
