<?php

namespace App\MessageHandler;

use App\Message\EpisodeNotification;
use App\NotificationPublisher;
use App\Repository\EpisodeRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class EpisodeNotificationHandler implements MessageHandlerInterface
{
    private $episodeRepository;
    private $publisher;

    public function __construct(EpisodeRepository $episodeRepository, NotificationPublisher $publisher)
    {
        $this->episodeRepository = $episodeRepository;
        $this->publisher = $publisher;
    }

    public function __invoke(EpisodeNotification $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($message->getCode());

        $this->publisher->publishEpisode($episode);
    }
}
