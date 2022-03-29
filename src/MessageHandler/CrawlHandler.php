<?php

namespace App\MessageHandler;

use App\Crawling\CrawlerInterface;
use App\Crawling\EpisodeCrawlerInterface;
use App\Message\Crawl;
use App\Repository\EpisodeRepository;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlHandler implements MessageHandlerInterface
{
    public function __construct(
        private ContainerInterface $crawlers,
        private EpisodeRepository $episodeRepository,
    ) {}

    public function __invoke(Crawl $message): void
    {
        /** @var CrawlerInterface|EpisodeCrawlerInterface $crawler */
        $crawler = $this->crawlers->get($message->crawler);

        if ($episodeCode = $message->episodeCode) {
            $episode = $this->episodeRepository->findOneByCode($episodeCode);
            $crawler->crawl($episode);
        } else {
            $crawler->crawl();
        }
    }
}
