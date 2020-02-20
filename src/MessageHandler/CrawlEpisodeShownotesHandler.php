<?php

namespace App\MessageHandler;

use App\Crawling\EpisodeShownotesCrawler;
use App\Message\CrawlEpisodeShownotes;
use App\Repository\EpisodeRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlEpisodeShownotesHandler implements MessageHandlerInterface
{
    private $crawler;
    private $episodeRepository;

    public function __construct(EpisodeRepository $episodeRepository, EpisodeShownotesCrawler $crawler)
    {
        $this->episodeRepository = $episodeRepository;
        $this->crawler = $crawler;
    }

    public function __invoke(CrawlEpisodeShownotes $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($message->getCode());

        $this->crawler->crawl($episode);
    }
}
