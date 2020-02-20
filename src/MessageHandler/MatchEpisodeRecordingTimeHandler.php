<?php

namespace App\MessageHandler;

use App\Crawling\EpisodeRecordingTimeMatcher;
use App\Message\MatchEpisodeRecordingTime;
use App\Repository\EpisodeRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MatchEpisodeRecordingTimeHandler implements MessageHandlerInterface
{
    private $matcher;
    private $episodeRepository;

    public function __construct(EpisodeRepository $episodeRepository, EpisodeRecordingTimeMatcher $matcher)
    {
        $this->episodeRepository = $episodeRepository;
        $this->matcher = $matcher;
    }

    public function __invoke(MatchEpisodeRecordingTime $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($message->getCode());

        $this->matcher->match($episode);
    }
}
