<?php

namespace App\EventListener;

use App\Entity\Episode;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'beforeAdminPersist',
            BeforeEntityUpdatedEvent::class => 'beforeAdminUpdated',
        ];
    }

    public function beforeAdminPersist(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof Episode) {
            $entity->modified();
        }
    }

    public function beforeAdminUpdated(BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof Episode) {
            $entity->modified();
        }
    }
}
