<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * @property CrawlingLogger $logger
 */
class EpisodeProcessor
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CrawlingProcessor $crawlingProcessor,
        private NotificationPublisher $publisher,
        private MailerInterface $mailer,
        private EpisodeRepository $episodeRepository,
    ) {
        $this->logger = new NullLogger();
    }

    public function prepare(Episode $episode): void
    {
        $this->logger->collect();

        $this->crawlingProcessor->crawl('cover', $episode);
        $this->crawlingProcessor->crawl('shownotes', $episode);
        $this->crawlingProcessor->crawl('transcript', $episode);
        $this->crawlingProcessor->crawl('duration', $episode);

        if (!$episode->isPublished()) {
            $episode->setPublished(true);

            $this->entityManager->persist($episode);
            $this->entityManager->flush();

            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->getConnection()->commit();
                $this->entityManager->getConnection()->beginTransaction();
            }

            $this->publisher->publish($episode);
        }

        $this->crawlingProcessor->crawl('recording_time', $episode);
        $this->crawlingProcessor->crawl('chat_archive', $episode);

        $logs = $this->logger->retrieve();

        if (!$adminEmail = $_SERVER['APP_ADMIN_EMAIL'] ?? null) {
            $this->logger->notice('No email has ben configured to send the episode publication report.');

            return;
        }

        $message = (new TemplatedEmail())
            ->from(new Address($_SERVER['MAILER_FROM'], $_SERVER['MAILER_FROM_AUTHOR']))
            ->to(new Address($adminEmail))
            ->subject(sprintf('Episode Publication Report: %s', $episode->getCode()))
            ->htmlTemplate('email/episode_publication_report.html.twig')
            ->context([
                'episode' => $episode,
                'logs' => $logs,
            ]);

        $this->mailer->send($message);
    }
}
