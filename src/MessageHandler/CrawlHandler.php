<?php

namespace App\MessageHandler;

use App\Crawling\CrawlingProcessor;
use App\Message\Crawl;
use App\Repository\EpisodeRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CrawlHandler implements MessageHandlerInterface
{
    public function __construct(
        private EpisodeRepository $episodeRepository,
        private CrawlingProcessor $crawlingProcessor,
    ) {}

    public function __invoke(Crawl $message): void
    {
        $episode = $message->episodeCode ? $this->episodeRepository->findOneByCode($message->episodeCode) : null;

        $this->crawlingProcessor->crawl($message->data, $episode, $message->lastModifiedAt, $message->initializedAt);
    }
}
