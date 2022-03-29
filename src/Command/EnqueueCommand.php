<?php

namespace App\Command;

use App\Crawling\Crawlers;
use App\Crawling\EpisodeFileCrawlerInterface;
use App\Crawling\FileDownloader;
use App\Entity\Episode;
use App\Message\Crawl;
use App\Message\CrawlFile;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EnqueueCommand extends CrawlCommand
{
    protected static $defaultName = 'enqueue';
    protected static $defaultDescription = 'Enqueues a crawling job';

    public function __construct(
        EntityManagerInterface $entityManager,
        EpisodeRepository $episodeRepository,
        FileDownloader $fileDownloader,
        ContainerInterface $crawlers,
        private MessageBusInterface $messenger,
    ) {
        parent::__construct($entityManager, $episodeRepository, $fileDownloader, $crawlers);
    }

    protected function preCrawl(string $data, OutputInterface $output): void {}

    protected function postCrawl(string $data, StyleInterface $style): void {}

    protected function crawl(string $data, StyleInterface $style): void
    {
        $this->messenger->dispatch(new Crawl(Crawlers::$crawlers[$data]));

        $style->success(sprintf('Enqueued crawling of %s.', $data));
    }

    protected function crawlEpisode(string $data, Episode $episode, StyleInterface $style): void
    {
        $crawlerName = Crawlers::$crawlers[$data];
        $crawler = $this->crawlers->get($crawlerName);

        if ($crawler instanceof EpisodeFileCrawlerInterface) {
            $this->messenger->dispatch(new CrawlFile($crawlerName, $episode->getCode()));
        } else {
            $this->messenger->dispatch(new Crawl($crawlerName, $episode->getCode()));
        }

        $style->success(sprintf('Enqueued crawling of %s for episode %s.', $data, $episode->getCode()));
    }
}
