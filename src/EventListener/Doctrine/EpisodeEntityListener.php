<?php

namespace App\EventListener\Doctrine;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Message\PrepareEpisode;
use App\Repository\UserRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

class EpisodeEntityListener implements EventSubscriber
{
    public function __construct(
        private MessageBusInterface $messenger,
        private UserRepository $userRepository,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            'postPersist',
        ];
    }

    public function postPersist(LifecycleEventArgs $event): void
    {
        $episode = $event->getEntity();
        $entityManager = $event->getEntityManager();

        if (!$episode instanceof Episode) {
            return;
        }

        $adminUser = $this->userRepository->findOneBy(['username' => $_SERVER['APP_ADMIN_USER']]);
        $chapter = (new EpisodeChapter())
            ->setEpisode($episode)
            ->setCreator($adminUser)
            ->setName('Start of Show')
            ->setStartsAt(0)
        ;

        $entityManager->persist($chapter);

        $this->messenger->dispatch(new PrepareEpisode($episode->getCode()));
    }
}
