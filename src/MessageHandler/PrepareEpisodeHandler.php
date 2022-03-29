<?php

namespace App\MessageHandler;

use App\Crawling\EpisodeChatArchiveMatcher;
use App\Crawling\EpisodeCoverCrawler;
use App\Crawling\EpisodeDurationCrawler;
use App\Crawling\EpisodeRecordingTimeMatcher;
use App\Crawling\EpisodeShownotesCrawler;
use App\Crawling\EpisodeTranscriptCrawler;
use App\Message\Crawl;
use App\Message\CrawlFile;
use App\Message\GenerateEpisodeReport;
use App\Message\PrepareEpisode;
use App\Message\PublishEpisode;
use App\Repository\EpisodeRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class PrepareEpisodeHandler implements MessageHandlerInterface
{
    public function __construct(
        private MessageBusInterface $messenger,
        private EpisodeRepository $episodeRepository,
    ) {}

    public function __invoke(PrepareEpisode $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($code = $message->code);

        $this->messenger->dispatch(new CrawlFile(EpisodeCoverCrawler::class, $code));
        $this->messenger->dispatch(new CrawlFile(EpisodeShownotesCrawler::class, $code));
        $this->messenger->dispatch(new CrawlFile(EpisodeTranscriptCrawler::class, $code));
        $this->messenger->dispatch(new CrawlFile(EpisodeDurationCrawler::class, $code));

        if (!$episode->isPublished()) {
            $this->messenger->dispatch(new PublishEpisode($code));
        }

        $this->messenger->dispatch(new Crawl(EpisodeRecordingTimeMatcher::class, $code));
        $this->messenger->dispatch(new Crawl(EpisodeChatArchiveMatcher::class, $code));

        $this->messenger->dispatch(new GenerateEpisodeReport($code));
    }
}
