<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Message\Crawl;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Mime\Address;

/**
 * @property CrawlingLogger $logger
 */
class EpisodeProcessor
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EpisodeRepository $episodeRepository,
        private readonly CrawlingProcessor $crawlingProcessor,
        private readonly MessageBusInterface $messenger,
        private readonly NotificationPublisher $publisher,
        private readonly MailerInterface $mailer,
    ) {
        $this->logger = new NullLogger();
    }

    public function prepare(Episode $episode): void
    {
        $this->logger->collect();

        $this->crawl($episode, 'cover');
        $this->crawl($episode, 'shownotes');
        $this->crawl($episode, 'transcript');
        $this->crawl($episode, 'duration');

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

        $this->crawl($episode, 'recording_time');
        $this->crawl($episode, 'chat_archive');

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

    private function crawl(Episode $episode, string $data): void
    {
        $result = $this->crawlingProcessor->crawl($data, $episode);

        if ($result->exception) {
            $message = new Crawl($data, $episode->getCode());
            $envelope = new Envelope($message, [
                DelayStamp::delayFor(new \DateInterval('15M')),
            ]);

            $this->messenger->dispatch($envelope);
        }
    }
}
