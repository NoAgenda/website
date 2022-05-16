<?php

namespace App\MessageHandler;

use App\Message\Crawl;
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
        $episode = $this->episodeRepository->findOneByCode($code = $message->episodeCode);

        $this->messenger->dispatch(new Crawl('cover', $code));
        $this->messenger->dispatch(new Crawl('shownotes', $code));
        $this->messenger->dispatch(new Crawl('transcript', $code));
        $this->messenger->dispatch(new Crawl('duration', $code));

        if (!$episode->isPublished()) {
            $this->messenger->dispatch(new PublishEpisode($code));
        }

        $this->messenger->dispatch(new Crawl('recording_time', $code));
        $this->messenger->dispatch(new Crawl('chat_archive', $code));

        $this->messenger->dispatch(new GenerateEpisodeReport($code));
    }
}
