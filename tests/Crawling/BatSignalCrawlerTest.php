<?php

namespace App\Tests\Crawling;

use App\Crawling\BatSignalCrawler;
use App\Crawling\NotificationPublisher;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Notifier\NotifierInterface;

class BatSignalCrawlerTest extends TestCase
{
    private $crawler;
    private $notificationPublisher;
    private $entityManager;
    private $httpClient;
    private $notifier;
    private $logger;
    private $repository;

    public function setUp(): void
    {
        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $this->notificationPublisher = $this->getMockBuilder(NotificationPublisher::class)->disableOriginalConstructor()->getMock();
        $this->repository = $this->getMockBuilder(BatSignalRepository::class)->disableOriginalConstructor()->getMock();
        $this->httpClient = new MockHttpClient();
        $this->notifier = $this->getMockBuilder(NotifierInterface::class)->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->crawler = new BatSignalCrawler($this->entityManager, $this->notificationPublisher, $this->repository, $this->httpClient, $this->notifier, 'foo', 1);
        $this->crawler->setLogger($this->logger);
    }

    public function testNewSignal(): void
    {
        $this->httpClient->setResponseFactory([
            $this->createResponse([[
                'id' => '404',
                'content' => 'We’re live now at noagendastream.com/ with No Agenda episode 33 #@pocketnoagenda',
                'created_at' => '2022-02-02 12:00:00',
            ]]),
        ]);

        $this->repository->expects($this->once())->method('exists')
            ->willReturn(false);

        $this->logger->expects($this->exactly(2))->method('info');

        $this->entityManager->expects($this->once())->method('persist');

        $this->crawler->crawl();
    }

    public function testMissingAccessToken(): void
    {
        $crawler = new BatSignalCrawler($this->entityManager, $this->notificationPublisher, $this->repository, $this->httpClient, $this->notifier, null, 1);
        $crawler->setLogger($this->logger);

        $this->logger->expects($this->once())->method('critical')
            ->with('Mastodon access token not found. Skipping crawling of bat signal.');

        $this->entityManager->expects($this->never())->method('persist');

        $crawler->crawl();
    }

    public function testInvalidResponse(): void
    {
        $this->httpClient->setResponseFactory([
            $this->createResponse([], 400),
        ]);

        $this->logger->expects($this->once())->method('warning')
            ->with('Failed to crawl bat signal on Mastodon: Response code 400');

        $this->logger->expects($this->once())->method('debug')
            ->with('No bat signal found.');

        $this->entityManager->expects($this->never())->method('persist');

        $this->crawler->crawl();
    }

    public function testNoSignal(): void
    {
        $this->httpClient->setResponseFactory([
            $this->createResponse([]),
        ]);

        $this->logger->expects($this->once())->method('debug')
            ->with('No bat signal found.');

        $this->entityManager->expects($this->never())->method('persist');

        $this->crawler->crawl();
    }

    public function testSignalExists(): void
    {
        $this->httpClient->setResponseFactory([
            $this->createResponse([[
                'id' => '404',
                'content' => 'We’re live now at noagendastream.com/ with No Agenda episode 33 #@pocketnoagenda',
                'created_at' => '2022-02-02 12:00:00',
            ]]),
        ]);

        $this->repository->expects($this->once())->method('exists')
            ->willReturn(true);

        $this->logger->expects($this->once())->method('debug')
            ->with('Found bat signal already exists.');

        $this->entityManager->expects($this->never())->method('persist');

        $this->crawler->crawl();
    }

    private function createResponse(array $body, int $statusCode = 200): MockResponse
    {
        return new MockResponse(json_encode($body), [
            'http_code' => $statusCode,
        ]);
    }
}
