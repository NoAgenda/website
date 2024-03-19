<?php

namespace App\Crawling;

use App\Entity\BatSignal;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Sentry\captureMessage;

class BatSignalCrawler implements CrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationPublisher $notificationPublisher,
        private readonly BatSignalRepository $batSignalRepository,
        private readonly HttpClientInterface $mastodonClient,
        private readonly NotifierInterface $notifier,
        private readonly ?string $mastodonAccessToken,
        private readonly int $mastodonAccountId,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(): void
    {
        if (!$this->mastodonAccessToken) {
            $this->logger->critical('Mastodon access token not found. Skipping crawling of bat signal.');

            return;
        }

        if (!$signal = $this->crawlBatSignal()) {
            $this->logger->debug('No bat signal found.');

            return;
        }

        if ($this->batSignalRepository->exists($signal)) {
            $this->logger->debug('Found bat signal already exists.');

            return;
        }

        $this->logger->info(sprintf(
            'Found new bat signal with code "%s" published at %s.',
            $signal->getCode(),
            $signal->getDeployedAt()->format('Y-m-d H:i:s'),
        ));

        $recent = (new \DateTime())->sub(new \DateInterval('PT1H'));

        if ($signal->getDeployedAt()->getTimestamp() >= $recent->getTimestamp()) {
            $this->notificationPublisher->boostMastodonPost($signal->postId);
            $this->notificationPublisher->sendUserLiveNotifications();
        } else {
            $this->logger->info('Bat signal was published more than an hour ago. Skipping live notifications.');
        }

        $this->entityManager->persist($signal);

        $this->notifier->send(new Notification('Bat signal has been published.'));
    }

    private function crawlBatSignal(): ?BatSignal
    {
        $response = $this->mastodonClient->request('GET', sprintf('accounts/%s/statuses', $this->mastodonAccountId));
        $responseCode = $response->getStatusCode();

        if ($responseCode >= 300) {
            $message = sprintf('Failed to crawl bat signal on Mastodon: Response code %s', $responseCode);
            $this->logger->warning($message);

            captureMessage($message);

            return null;
        }

        $entries = json_decode($response->getContent(), true);

        foreach ($entries as $entry) {
            if (str_contains($entry['content'] ?? '', 'We’re live') && str_contains($entry['content'] ?? '', 'No Agenda')) {
                preg_match('/episode (\d+)/', $entry['content'],$matches);
                list(, $code) = $matches;

                $signal = (new BatSignal())
                    ->setCode($code)
                    ->setDeployedAt(new \DateTime($entry['created_at'] ?? null));

                $signal->postId = $entry['id'];

                return $signal;
            }
        }

        return null;
    }
}
