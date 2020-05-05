<?php

namespace App\MessageHandler;

use App\Crawling\YoutubeCrawler;
use App\Message\CrawlYoutube;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlYoutubeHandler implements MessageHandlerInterface
{
    private $crawler;

    public function __construct(YoutubeCrawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function __invoke(CrawlYoutube $message): void
    {
        $this->crawler->crawl();
    }
}
