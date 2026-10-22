<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Entity\NotificationSubscription;
use App\Repository\MastodonAnnouncementRepository;
use App\Repository\NotificationSubscriptionRepository;
use App\Twig\CoverExtension;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Sentry\Severity;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Sentry\captureException;
use function Sentry\captureMessage;

class NotificationPublisher
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly NotificationSubscriptionRepository $notificationSubscriptionRepository,
        private readonly MastodonAnnouncementRepository $mastodonAnnouncementRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly HttpClientInterface $mastodonClient,
        private readonly HttpClientInterface $secondaryMastodonClient,
        private readonly RouterInterface $router,
        private readonly ?WebPush $pushNotificationProcessor,
        private readonly CoverExtension $coverExtension,
        private readonly ?string $mastodonAccessToken,
        private readonly ?string $secondaryMastodonAccessToken,
        private readonly bool $mastodonPublish,
    ) {
        $this->logger = new NullLogger();
    }

    public function sendUserEpisodeNotifications(Episode $episode): void
    {
        if (!$this->pushNotificationProcessor) {
            $this->logger->info('Push notifications were not enabled');

            return;
        }

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

    public function sendUserLiveNotifications(string $title, string $uri): void
    {
        if (!$this->pushNotificationProcessor) {
            $this->logger->notice('Push notifications were not enabled');

            return;
        }

        $notificationPayload = json_encode([
            'title' => $title,
            'body' => 'Listen live',
            'uri' => $uri,
        ]);

        $this->logger->debug('Sending live notifications');

        foreach ($this->notificationSubscriptionRepository->findByType('live') as $notificationSubscription) {
            $this->sendNotification($notificationSubscription, $notificationPayload);
        }

        $this->notificationSubscriptionRepository->flush();
    }

    public function publishMastodonEpisodeAnnouncement(Episode $episode): void
    {
        $code = $episode->getCode();
        $title = sprintf('No Agenda Episode %s - %s', $code, $episode->getName());
        $uri = $this->router->generate('podcast_episode', ['code' => $code], RouterInterface::ABSOLUTE_URL);

        $this->publishMastodonStatus("$title $uri", sprintf('episode %s', $code), $episode->getCoverUri(), $title);
    }

    public function publishMastodonLiveAnnouncement(string $title, string $uri, ?string $imageUri): void
    {
        $this->publishMastodonStatus("$title $uri", 'live item', $imageUri, $title);
    }

    private function publishMastodonStatus(string $status, string $description, ?string $imageUri, string $imageDescription): void
    {
        if (!$this->mastodonPublish) {
            $this->logger->info('Publishing to Mastodon has been disabled');

            return;
        }

        $image = $imageUri ? $this->downloadImage($imageUri) : null;

        $accounts = [
            'primary' => [$this->mastodonClient, $this->mastodonAccessToken],
            'secondary' => [$this->secondaryMastodonClient, $this->secondaryMastodonAccessToken],
        ];

        foreach ($accounts as $account => [$client, $accessToken]) {
            if (!$accessToken) {
                $this->logger->info(sprintf('%s Mastodon access token not found. Skipping %s notification.', ucfirst($account), $description));

                continue;
            }

            $this->logger->debug(sprintf('Publishing Mastodon post for %s to %s account', $description, $account));

            try {
                $this->mastodonAnnouncementRepository->withAccountLock($account, function () use ($client, $account, $description, $image, $imageDescription, $status): void {
                $mediaId = $image ? $this->uploadMastodonImage($client, $image, $imageDescription, $account) : null;
                $body = ['status' => $status];

                if ($mediaId) {
                    $body['media_ids[]'] = $mediaId;
                }

                $response = $client->request('POST', 'statuses', [
                    'body' => http_build_query($body),
                ]);

                if (($statusCode = $response->getStatusCode()) < 200 || $statusCode >= 300) {
                    $message = sprintf('Failed to publish %s notification to %s Mastodon account: Response code %s', $description, $account, $statusCode);
                    $this->logger->warning($message);

                    captureMessage($message);

                    return;
                }

                $statusId = json_decode($response->getContent(), true)['id'] ?? null;
                if (!is_string($statusId) || '' === $statusId) {
                    $message = sprintf('Mastodon returned no status ID for %s on the %s account; retention was not updated.', $description, $account);
                    $this->logger->warning($message);
                    captureMessage($message);

                    return;
                }

                $pendingStatusIds = $this->mastodonAnnouncementRepository->recordPublishedStatus($account, $statusId);
                $this->deletePendingMastodonStatuses($client, $account, $pendingStatusIds);
                });
            } catch (\Throwable $exception) {
                $this->logger->critical(
                    sprintf('An exception occurred while publishing %s on the %s Mastodon account: %s', $description, $account, $exception->getMessage()),
                    ['exception' => $exception],
                );

                captureException($exception);
            }
        }
    }

    public function retryMastodonRetentionCleanup(): void
    {
        if (!$this->mastodonPublish) {
            return;
        }

        $clients = [
            'primary' => [$this->mastodonClient, $this->mastodonAccessToken],
            'secondary' => [$this->secondaryMastodonClient, $this->secondaryMastodonAccessToken],
        ];

        foreach ($this->mastodonAnnouncementRepository->findWithPendingDeletions() as $announcement) {
            if (!isset($clients[$announcement->getAccount()])) {
                $this->logger->error(sprintf('Ignoring retention row for unknown Mastodon account "%s".', $announcement->getAccount()));
                continue;
            }

            [$client, $accessToken] = $clients[$announcement->getAccount()];
            if ($accessToken) {
                $this->deletePendingMastodonStatuses($client, $announcement->getAccount(), $announcement->getPendingDeletionIds());
            }
        }
    }

    /** @param string[] $statusIds */
    private function deletePendingMastodonStatuses(HttpClientInterface $client, string $account, array $statusIds): void
    {
        foreach ($statusIds as $statusId) {
            if ('' === $statusId) {
                continue;
            }

            try {
                $response = $client->request('DELETE', 'statuses/' . rawurlencode($statusId));
                $statusCode = $response->getStatusCode();

                if (($statusCode >= 200 && $statusCode < 300) || 404 === $statusCode) {
                    $this->mastodonAnnouncementRepository->clearPendingDeletion($account, $statusId);
                } else {
                    $this->logger->warning(sprintf('Failed to delete tracked Mastodon status %s from %s account: Response code %s', $statusId, $account, $statusCode));
                }
            } catch (\Throwable $exception) {
                $this->logger->warning(sprintf('Failed to delete tracked Mastodon status %s from %s account: %s', $statusId, $account, $exception->getMessage()));
                captureException($exception);
            }
        }
    }

    private function downloadImage(string $uri): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $uri);

            if (200 !== $statusCode = $response->getStatusCode()) {
                $this->logger->warning(sprintf('Failed to download Mastodon image: Response code %s', $statusCode));

                return null;
            }

            $contentType = strtok($response->getHeaders()['content-type'][0] ?? 'image/jpeg', ';');

            if (!str_starts_with($contentType, 'image/')) {
                $this->logger->warning(sprintf('Mastodon image has invalid content type "%s"', $contentType));

                return null;
            }

            $filename = basename(parse_url($uri, PHP_URL_PATH)) ?: 'album-art.jpg';

            return [$response->getContent(), $filename, $contentType];
        } catch (\Throwable $exception) {
            $this->logger->warning(sprintf('Failed to download Mastodon image: %s', $exception->getMessage()));

            captureException($exception);

            return null;
        }
    }

    private function uploadMastodonImage(HttpClientInterface $client, array $image, string $description, string $account): ?string
    {
        try {
            [$content, $filename, $contentType] = $image;
            $formData = new FormDataPart([
                'file' => new DataPart($content, $filename, $contentType),
                'description' => $description,
            ]);
            $response = $client->request('POST', 'media', [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
            ]);

            if (200 !== $statusCode = $response->getStatusCode()) {
                $this->logger->warning(sprintf('Failed to upload image to %s Mastodon account: Response code %s; publishing without image', $account, $statusCode));

                return null;
            }

            return json_decode($response->getContent(), true)['id'] ?? null;
        } catch (\Throwable $exception) {
            $this->logger->warning(sprintf('Failed to upload image to %s Mastodon account: %s; publishing without image', $account, $exception->getMessage()));

            captureException($exception);

            return null;
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
