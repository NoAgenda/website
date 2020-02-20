<?php

namespace App\MessageHandler;

use App\Crawling\FeedCrawler;
use App\Message\CrawlFeed;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlFeedHandler implements MessageHandlerInterface
{
    private $crawler;

    public function __construct(FeedCrawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function __invoke(CrawlFeed $message): void
    {
        $this->crawler->crawl();
    }
}
