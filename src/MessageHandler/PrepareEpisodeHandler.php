<?php

namespace App\MessageHandler;

use App\Crawling\EpisodeProcessor;
use App\Message\PrepareEpisode;
use App\Repository\EpisodeRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PrepareEpisodeHandler implements MessageHandlerInterface
{
    public function __construct(
        private EpisodeRepository $episodeRepository,
        private EpisodeProcessor $episodeProcessor,
    ) {}

    public function __invoke(PrepareEpisode $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($message->episodeCode);

        $this->episodeProcessor->prepare($episode);
    }
}
