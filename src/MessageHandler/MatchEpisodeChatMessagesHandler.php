<?php

namespace App\MessageHandler;

use App\Crawling\EpisodeChatMessagesMatcher;
use App\Message\MatchEpisodeChatMessages;
use App\Repository\EpisodeRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MatchEpisodeChatMessagesHandler implements MessageHandlerInterface
{
    private $matcher;
    private $episodeRepository;

    public function __construct(EpisodeRepository $episodeRepository, EpisodeChatMessagesMatcher $matcher)
    {
        $this->episodeRepository = $episodeRepository;
        $this->matcher = $matcher;
    }

    public function __invoke(MatchEpisodeChatMessages $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($message->getCode());

        $this->matcher->match($episode);
    }
}
