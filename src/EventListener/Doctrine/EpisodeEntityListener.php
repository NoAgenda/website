<?php

namespace App\EventListener\Doctrine;

use App\Entity\Episode;
use App\Message\PrepareEpisode;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

class EpisodeEntityListener implements EventSubscriber
{
    public function __construct(
        private MessageBusInterface $messenger,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            'prePersist',
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $episode = $event->getEntity();

        if (!$episode instanceof Episode) {
            return;
        }

        $this->messenger->dispatch(new PrepareEpisode($episode->getCode()));
    }
}
