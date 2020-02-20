<?php

namespace App\MessageHandler;

use App\Crawling\TranscriptCrawler;
use App\Message\CrawlTranscripts;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlTranscriptsHandler implements MessageHandlerInterface
{
    private $crawler;

    public function __construct(TranscriptCrawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function __invoke(CrawlTranscripts $message): void
    {
        $this->crawler->crawl();
    }
}
