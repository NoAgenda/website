<?php

namespace App\MessageHandler;

use App\Crawling\EpisodeFilesCrawler;
use App\Message\CrawlEpisodeTranscript;
use App\Repository\EpisodeRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlEpisodeTranscriptHandler implements MessageHandlerInterface
{
    private $crawler;
    private $episodeRepository;

    public function __construct(EpisodeRepository $episodeRepository, EpisodeFilesCrawler $crawler)
    {
        $this->episodeRepository = $episodeRepository;
        $this->crawler = $crawler;
    }

    public function __invoke(CrawlEpisodeTranscript $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($message->getCode());

        $this->crawler->crawlTranscript($episode);
    }
}
