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
    public function testPublishesLiveAnnouncementToBothMastodonAccounts(): void
    {
        $requests = [];
        $responseFactory = function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$method, $url, $options['body']];

            return new MockResponse('{}', ['http_code' => 200]);
        };

        $publisher = new NotificationPublisher(
            $this->getMockBuilder(NotificationSubscriptionRepository::class)->disableOriginalConstructor()->getMock(),
            new MockHttpClient($responseFactory, 'https://primary.example/api/v1/'),
            new MockHttpClient($responseFactory, 'https://secondary.example/api/v1/'),
            $this->createMock(RouterInterface::class),
            null,
            $this->getMockBuilder(CoverExtension::class)->disableOriginalConstructor()->getMock(),
            'primary-token',
            'secondary-token',
            true,
        );

        $publisher->publishMastodonLiveAnnouncement('No Agenda 1889 Live', 'https://example.com/live');

        $this->assertSame([
            ['POST', 'https://primary.example/api/v1/statuses', 'status=No+Agenda+1889+Live+https%3A%2F%2Fexample.com%2Flive'],
            ['POST', 'https://secondary.example/api/v1/statuses', 'status=No+Agenda+1889+Live+https%3A%2F%2Fexample.com%2Flive'],
        ], $requests);
    }
}
