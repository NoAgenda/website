<?php

namespace App\EventListener\Doctrine;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Message\PrepareEpisode;
use App\Repository\UserAccountRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EpisodeEntityListener implements EventSubscriber
{
    public function __construct(
        private readonly MessageBusInterface $crawlingBus,
        private readonly UserAccountRepository $accountRepository,
        private readonly LoggerInterface $logger,
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

        $adminUsername = $_SERVER['APP_ADMIN_USER'];
        $adminAccount = $this->accountRepository->findOneBy(['username' => $adminUsername]);

        if ($adminAccount) {
            $chapter = (new EpisodeChapter())
                ->setEpisode($episode)
                ->setCreator($adminAccount->getUser())
                ->setName('Start of Show')
                ->setStartsAt(0);

            $entityManager->persist($chapter);
        } else {
            $this->logger->warning(sprintf('Unable to create automatic chapter because the admin user "%s" does not exist.', $adminUsername));
        }

        $this->crawlingBus->dispatch(new PrepareEpisode($episode->getCode()));
    }
}
