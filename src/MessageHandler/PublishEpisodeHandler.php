<?php

namespace App\MessageHandler;

use App\Crawling\NotificationPublisher;
use App\Entity\EpisodeChapter;
use App\Message\PublishEpisode;
use App\Repository\EpisodeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PublishEpisodeHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EpisodeRepository $episodeRepository,
        private UserRepository $userRepository,
        private NotificationPublisher $publisher,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(PublishEpisode $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($message->episodeCode);

        if ($episode->isPublished()) {
            // An episode can only be published once
            return;
        }

        $defaultUser = $this->userRepository->findOneBy(['username' => 'Woodstock']);
        $chapter = (new EpisodeChapter())
            ->setEpisode($episode)
            ->setCreator($defaultUser)
            ->setName('Start of Show')
            ->setStartsAt(0)
        ;

        $this->entityManager->persist($chapter);

        try {
            $this->publisher->publish($episode);
        } catch (\Exception $exception) {
            $this->logger->critical('Failed to publish episode on Mastodon.', ['exception' => $exception]);
        }

        $episode->setPublished(true);

        $this->entityManager->persist($episode);
    }
}
