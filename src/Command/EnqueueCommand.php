<?php

namespace App\Command;

use App\Crawling\CrawlingResult;
use App\Entity\Episode;
use Symfony\Component\Console\Style\StyleInterface;

class EnqueueCommand extends CrawlCommand
{
    protected static $defaultName = 'enqueue';
    protected static $defaultDescription = 'Enqueues a crawling job';

    protected function preCrawl(): void {}

    protected function postCrawl(array $results, StyleInterface $style): void {}

    protected function crawl(string $data, StyleInterface $style): ?CrawlingResult
    {
        $this->crawlingProcessor->enqueue($data);

        $style->success(sprintf('Enqueued crawling of %s.', $data));

        return null;
    }

    protected function crawlEpisode(string $data, Episode $episode, StyleInterface $style): ?CrawlingResult
    {
        $this->crawlingProcessor->enqueue($data, $episode);

        $style->success(sprintf('Enqueued crawling of %s for episode %s.', $data, $episode->getCode()));

        return null;
    }
}
