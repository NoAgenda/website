<?php

namespace App\MessageHandler;

use App\Crawling\EpisodeFilesCrawler;
use App\Message\CrawlEpisodeFiles;
use App\Repository\EpisodeRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlEpisodeFilesHandler implements MessageHandlerInterface
{
    private $crawler;
    private $episodeRepository;

    public function __construct(EpisodeRepository $episodeRepository, EpisodeFilesCrawler $crawler)
    {
        $this->episodeRepository = $episodeRepository;
        $this->crawler = $crawler;
    }

    public function __invoke(CrawlEpisodeFiles $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($message->getCode());

        $this->crawler->crawl($episode);
    }
}
