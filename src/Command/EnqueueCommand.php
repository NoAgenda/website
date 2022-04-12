<?php

namespace App\Command;

use App\Entity\Episode;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

class EnqueueCommand extends CrawlCommand
{
    protected static $defaultName = 'enqueue';
    protected static $defaultDescription = 'Enqueues a crawling job';

    protected function preCrawl(string $data, OutputInterface $output): void {}

    protected function postCrawl(string $data, StyleInterface $style): void {}

    protected function crawl(string $data, StyleInterface $style): void
    {
        $this->crawlingProcessor->enqueue($data);

        $style->success(sprintf('Enqueued crawling of %s.', $data));
    }

    protected function crawlEpisode(string $data, Episode $episode, StyleInterface $style): void
    {
        $this->crawlingProcessor->enqueue($data, $episode);

        $style->success(sprintf('Enqueued crawling of %s for episode %s.', $data, $episode->getCode()));
    }
}
