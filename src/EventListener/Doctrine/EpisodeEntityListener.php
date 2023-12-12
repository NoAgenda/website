<?php

namespace App\EventListener\Doctrine;

use App\Entity\Episode;
use App\Message\PrepareEpisode;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

class EpisodeEntityListener implements EventSubscriber
{
    public function __construct(
        private readonly MessageBusInterface $crawlingBus,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            'postPersist',
        ];
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $episode = $event->getObject();

        if (!$episode instanceof Episode) {
            return;
        }

        $this->crawlingBus->dispatch(new PrepareEpisode($episode->getCode()));
    }
}
