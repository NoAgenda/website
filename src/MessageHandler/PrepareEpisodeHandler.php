<?php

namespace App\MessageHandler;

use App\Crawling\CrawlingLogger;
use App\Crawling\CrawlingProcessor;
use App\Crawling\NotificationPublisher;
use App\Message\PrepareEpisode;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PrepareEpisodeHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CrawlingProcessor $crawlingProcessor,
        private NotificationPublisher $publisher,
        private MailerInterface $mailer,
        private CrawlingLogger $logger,
        private EpisodeRepository $episodeRepository,
    ) {}

    public function __invoke(PrepareEpisode $message): void
    {
        $episode = $this->episodeRepository->findOneByCode($code = $message->episodeCode);

        $this->logger->collect();

        $this->crawlingProcessor->crawl('cover', $episode);
        $this->crawlingProcessor->crawl('shownotes', $episode);
        $this->crawlingProcessor->crawl('transcript', $episode);
        $this->crawlingProcessor->crawl('duration', $episode);

        if (!$episode->isPublished()) {
            $episode->setPublished(true);

            $this->entityManager->persist($episode);
            $this->entityManager->flush();

            $this->publisher->publish($episode);
        }

        $this->crawlingProcessor->crawl('recording_time', $episode);
        $this->crawlingProcessor->crawl('chat_archive', $episode);

        $logs = $this->logger->retrieve();

        if (!$adminEmail = $_SERVER['APP_ADMIN_EMAIL'] ?? null) {
            return;
        }

        $message = (new TemplatedEmail())
            ->from($_SERVER['MAILER_FROM'], $_SERVER['MAILER_FROM_AUTHOR'])
            ->to($adminEmail, $_SERVER['APP_ADMIN_USER'])
            ->subject(sprintf('Episode Publication Report: %s', $code))
            ->htmlTemplate('email/episode_publication_report.html.twig')
            ->context([
                'episode' => $episode,
                'logs' => $logs,
            ]);

        $this->mailer->send($message);
    }
}
