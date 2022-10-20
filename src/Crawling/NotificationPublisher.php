<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Entity\NotificationSubscription;
use App\Repository\NotificationSubscriptionRepository;
use App\Twig\CoverExtension;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Sentry\Severity;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Sentry\captureException;
use function Sentry\captureMessage;

class NotificationPublisher
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly NotificationSubscriptionRepository $notificationSubscriptionRepository,
        private readonly HttpClientInterface $mastodonClient,
        private readonly RouterInterface $router,
        private readonly WebPush $pushNotificationProcessor,
        private readonly CoverExtension $coverExtension,
        private readonly ?string $mastodonAccessToken,
        private readonly bool $mastodonPublish,
    ) {
        $this->logger = new NullLogger();
    }

    public function sendUserEpisodeNotifications(Episode $episode): void
    {
        $notificationPayload = json_encode([
            'title' => sprintf('No Agenda %s', $episode),
            'body' => 'A new episode is available',
            'icon' => $this->coverExtension->episodeCover($episode),
            'uri' => $this->router->generate('podcast_episode', ['code' => $episode->getCode()], RouterInterface::ABSOLUTE_URL),
        ]);

        $this->logger->debug(sprintf('Sending notifications for episode %s', $episode->getCode()));

        foreach ($this->notificationSubscriptionRepository->findByType('episode') as $notificationSubscription) {
            $this->sendNotification($notificationSubscription, $notificationPayload);
        }

        $this->notificationSubscriptionRepository->flush();
    }

    public function sendUserLiveNotifications(): void
    {
        // todo grab image and episode number from bat signal post

        $notificationPayload = json_encode([
            'title' => 'No Agenda is now live',
            'body' => 'Listen on the No Agenda Stream',
            'uri' => $this->router->generate('livestream', [], RouterInterface::ABSOLUTE_URL),
        ]);

        $this->logger->debug('Sending live notifications');

        foreach ($this->notificationSubscriptionRepository->findByType('live') as $notificationSubscription) {
            $this->sendNotification($notificationSubscription, $notificationPayload);
        }
    }

    public function publishMastodonEpisodeAnnouncement(Episode $episode): void
    {
        if (!$this->mastodonAccessToken) {
            $this->logger->info('Mastodon access token not found. Skipping publishing of episode notification.');

            return;
        }

        if (!$this->mastodonPublish) {
            $this->logger->info('Publishing to Mastodon has been disabled');

            return;
        }

        $this->logger->debug(sprintf('Publishing Mastodon post for episode %s', $episode->getCode()));

        try {
            $code = $episode->getCode();
            $title = sprintf('No Agenda Episode %s - %s', $code, $episode->getName());
            $path = $this->router->generate('podcast_episode', ['code' => $code], RouterInterface::ABSOLUTE_URL);

            $response = $this->mastodonClient->request('POST', 'statuses', [
                'body' => http_build_query([
                    'status' => "$title $path",
                ]),
            ]);

            if (200 !== $statusCode = $response->getStatusCode()) {
                $message = sprintf('Failed to publish episode notification to Mastodon: Response code %s', $statusCode);
                $this->logger->warning($message);

                captureMessage($message);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(sprintf('An exception occurred while publishing an episode on Mastodon: %s', $exception->getMessage()), ['exception' => $exception]);

            captureException($exception);
        }
    }

    public function boostMastodonPost(string $postId): void
    {
        if (!$this->mastodonAccessToken) {
            $this->logger->info('Mastodon access token not found. Skipping boost of post.');

            return;
        }

        if (!$this->mastodonPublish) {
            $this->logger->info('Publishing to Mastodon has been disabled');

            return;
        }

        $this->logger->debug('Boosting post on Mastodon');

        try {
            $response = $this->mastodonClient->request('POST', sprintf('statuses/%s/reblog', $postId));

            if (200 !== $statusCode = $response->getStatusCode()) {
                $message = sprintf('Failed to boost post on Mastodon: Response code %s', $statusCode);
                $this->logger->warning($message);

                captureMessage($message);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(sprintf('An exception occurred while boosting a post on Mastodon: %s', $exception->getMessage()), ['exception' => $exception]);

            captureException($exception);
        }
    }

    private function sendNotification(NotificationSubscription $notificationSubscription, string $notificationPayload): void
    {
        try {
            $pushSubscription = Subscription::create($notificationSubscription->getSubscription());

            $report = $this->pushNotificationProcessor->sendOneNotification($pushSubscription, $notificationPayload);

            if (!$report->isSuccess()) {
                if ($report->isSubscriptionExpired()) {
                    $this->logger->debug(sprintf('Notification subscription #%s has expired', $notificationSubscription->getId()));
                    $this->notificationSubscriptionRepository->remove($notificationSubscription);
                } else {
                    $message = sprintf('Failed to send push notification for subscription #%s: %s', $notificationSubscription->getId(), $report->getReason());
                    $this->logger->warning($message);

                    captureMessage($message, Severity::warning());
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(
                sprintf('An exception occurred while sending push notification for subscription #%s: %s', $notificationSubscription->getId(), $exception->getMessage()),
                ['exception' => $exception]
            );

            captureException($exception);
        }
    }
}
