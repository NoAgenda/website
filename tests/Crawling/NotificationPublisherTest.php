<?php

namespace App\Tests\Crawling;

use App\Crawling\NotificationPublisher;
use App\Repository\MastodonAnnouncementRepository;
use App\Repository\NotificationSubscriptionRepository;
use App\Twig\CoverExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Routing\RouterInterface;

class NotificationPublisherTest extends TestCase
{
    public function testPublishesLiveAnnouncementWithImageToBothMastodonAccounts(): void
    {
        $requests = [];
        $repository = $this->createMock(MastodonAnnouncementRepository::class);
        $repository->expects($this->exactly(2))->method('recordPublishedStatus')->willReturn([]);
        $publisher = $this->createPublisher($requests, repository: $repository);

        $publisher->publishMastodonLiveAnnouncement(
            'No Agenda 1889 Live',
            'https://example.com/live',
            'https://example.com/1889.jpg',
        );

        $this->assertSame([
            ['POST', 'https://primary.example/api/v1/media'],
            ['POST', 'https://primary.example/api/v1/statuses', 'status=No+Agenda+1889+Live+https%3A%2F%2Fexample.com%2Flive&media_ids%5B%5D=primary-media'],
            ['POST', 'https://secondary.example/api/v1/media'],
            ['POST', 'https://secondary.example/api/v1/statuses', 'status=No+Agenda+1889+Live+https%3A%2F%2Fexample.com%2Flive&media_ids%5B%5D=secondary-media'],
        ], $requests);
    }

    public function testPublishesWithoutImageWhenUploadFails(): void
    {
        $requests = [];
        $repository = $this->createMock(MastodonAnnouncementRepository::class);
        $repository->expects($this->exactly(2))->method('recordPublishedStatus')->willReturnCallback(
            static fn (string $account, string $statusId): array => [],
        );
        $publisher = $this->createPublisher($requests, 403, repository: $repository);

        $publisher->publishMastodonLiveAnnouncement(
            'No Agenda 1889 Live',
            'https://example.com/live',
            'https://example.com/1889.jpg',
        );

        $this->assertSame(
            ['POST', 'https://primary.example/api/v1/statuses', 'status=No+Agenda+1889+Live+https%3A%2F%2Fexample.com%2Flive'],
            $requests[1],
        );
    }

    public function testDeletesPreviousTrackedStatusOnlyAfterSuccessfulPublish(): void
    {
        $requests = [];
        $repository = $this->createMock(MastodonAnnouncementRepository::class);
        $repository->expects($this->exactly(2))->method('recordPublishedStatus')->willReturnCallback(
            static fn (string $account, string $statusId): array => $account === 'primary' ? ['old-primary'] : [],
        );
        $repository->expects($this->once())->method('clearPendingDeletion')->with('primary', 'old-primary');
        $publisher = $this->createPublisher($requests, repository: $repository);

        $publisher->publishMastodonLiveAnnouncement('Live', 'https://example.com/live', null);

        self::assertSame(['DELETE', 'https://primary.example/api/v1/statuses/old-primary'], $requests[1]);
    }

    public function testLeavesFailedDeletionPending(): void
    {
        $requests = [];
        $repository = $this->createMock(MastodonAnnouncementRepository::class);
        $repository->expects($this->exactly(2))->method('recordPublishedStatus')->willReturnCallback(
            static fn (string $account, string $statusId): array => $account === 'primary' ? ['old-primary'] : [],
        );
        $repository->expects($this->never())->method('clearPendingDeletion');
        $publisher = $this->createPublisher($requests, repository: $repository, primaryDeleteStatus: 500);

        $publisher->publishMastodonLiveAnnouncement('Live', 'https://example.com/live', null);

        self::assertSame(['DELETE', 'https://primary.example/api/v1/statuses/old-primary'], $requests[1]);
    }

    public function testFailedPublishDoesNotRecordOrDeletePreviousStatus(): void
    {
        $requests = [];
        $repository = $this->createMock(MastodonAnnouncementRepository::class);
        $repository->expects($this->once())->method('recordPublishedStatus')->with('secondary', 'secondary-status')->willReturn([]);
        $publisher = $this->createPublisher($requests, repository: $repository, primaryStatusStatus: 503);

        $publisher->publishMastodonLiveAnnouncement('Live', 'https://example.com/live', null);

        self::assertCount(2, $requests);
        self::assertSame('POST', $requests[0][0]);
        self::assertSame('POST', $requests[1][0]);
    }

    private function createPublisher(
        array &$requests,
        int $primaryMediaStatus = 200,
        ?MastodonAnnouncementRepository $repository = null,
        int $primaryDeleteStatus = 204,
        int $primaryStatusStatus = 200,
    ): NotificationPublisher
    {
        $responseFactory = function (string $method, string $url, array $options) use (&$requests, $primaryMediaStatus, $primaryDeleteStatus, $primaryStatusStatus): MockResponse {
            if (str_ends_with($url, '/media')) {
                $requests[] = [$method, $url];
                $status = str_contains($url, 'primary.example') ? $primaryMediaStatus : 200;
                $id = str_contains($url, 'primary.example') ? 'primary-media' : 'secondary-media';

                return new MockResponse(json_encode(['id' => $id]), ['http_code' => $status]);
            }

            if ('DELETE' === $method) {
                $requests[] = [$method, $url];

                return new MockResponse('{}', ['http_code' => str_contains($url, 'primary.example') ? $primaryDeleteStatus : 204]);
            }

            $requests[] = [$method, $url, $options['body']];

            return new MockResponse(json_encode(['id' => str_contains($url, 'primary.example') ? 'primary-status' : 'secondary-status']), ['http_code' => str_contains($url, 'primary.example') ? $primaryStatusStatus : 200]);
        };

        $repository = $repository ?? $this->createMock(MastodonAnnouncementRepository::class);
        $repository->method('withAccountLock')->willReturnCallback(static function (string $account, \Closure $callback): void {
            $callback();
        });

        return new NotificationPublisher(
            $this->getMockBuilder(NotificationSubscriptionRepository::class)->disableOriginalConstructor()->getMock(),
            $repository ?? $this->createMock(MastodonAnnouncementRepository::class),
            new MockHttpClient(new MockResponse('image', ['response_headers' => ['content-type: image/jpeg']])),
            new MockHttpClient($responseFactory, 'https://primary.example/api/v1/'),
            new MockHttpClient($responseFactory, 'https://secondary.example/api/v1/'),
            $this->createMock(RouterInterface::class),
            null,
            $this->getMockBuilder(CoverExtension::class)->disableOriginalConstructor()->getMock(),
            'primary-token',
            'secondary-token',
            true,
        );
    }
}
