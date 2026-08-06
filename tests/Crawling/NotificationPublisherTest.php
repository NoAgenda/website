<?php

namespace App\Tests\Crawling;

use App\Crawling\NotificationPublisher;
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
        $publisher = $this->createPublisher($requests);

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
        $publisher = $this->createPublisher($requests, 403);

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

    private function createPublisher(array &$requests, int $primaryMediaStatus = 200): NotificationPublisher
    {
        $responseFactory = function (string $method, string $url, array $options) use (&$requests, $primaryMediaStatus): MockResponse {
            if (str_ends_with($url, '/media')) {
                $requests[] = [$method, $url];
                $status = str_contains($url, 'primary.example') ? $primaryMediaStatus : 200;
                $id = str_contains($url, 'primary.example') ? 'primary-media' : 'secondary-media';

                return new MockResponse(json_encode(['id' => $id]), ['http_code' => $status]);
            }

            $requests[] = [$method, $url, $options['body']];

            return new MockResponse('{}', ['http_code' => 200]);
        };

        return new NotificationPublisher(
            $this->getMockBuilder(NotificationSubscriptionRepository::class)->disableOriginalConstructor()->getMock(),
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
