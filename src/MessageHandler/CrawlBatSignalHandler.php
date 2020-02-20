<?php

namespace App\MessageHandler;

use App\Crawling\BatSignalCrawler;
use App\Message\CrawlBatSignal;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlBatSignalHandler implements MessageHandlerInterface
{
    private $crawler;

    public function __construct(BatSignalCrawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function __invoke(CrawlBatSignal $message): void
    {
        $this->crawler->crawl();
    }
}
