<?php

namespace App\Tests\Crawling;

use App\Crawling\BatSignalCrawler;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClientInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class BatSignalCrawlerTest extends TestCase
{
    private $crawler;
    private $entityManager;
    private $httpClient;
    private $logger;
    private $repository;

    public function setUp(): void
    {
        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $this->repository = $this->getMockBuilder(BatSignalRepository::class)->disableOriginalConstructor()->getMock();
        $this->httpClient = $this->getMockBuilder(HttpMethodsClientInterface::class)->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->crawler = new BatSignalCrawler($this->entityManager, $this->repository, $this->httpClient, 'foo', 1);
        $this->crawler->setLogger($this->logger);
    }

    public function testNewSignal(): void
    {
        $response = $this->createResponse([[
            'content' => 'We\'re live now at noagendastream.com/ with No Agenda episode 33 #@pocketnoagenda',
            'created_at' => '2022-02-02 12:00:00',
        ]]);
        $this->httpClient->expects($this->once())->method('get')
            ->willReturn($response)
        ;

        $this->repository->expects($this->once())->method('exists')
            ->willReturn(false)
        ;

        $this->logger->expects($this->once())->method('info')
            ->with('Found new bat signal with code "33" published at 2022-02-02 12:00:00.')
        ;

        $this->entityManager->expects($this->once())->method('persist');

        $this->crawler->crawl();
    }

    public function testMissingAccessToken(): void
    {
        $crawler = new BatSignalCrawler($this->entityManager, $this->repository, $this->httpClient, null, 1);
        $crawler->setLogger($this->logger);

        $this->logger->expects($this->once())->method('critical')
            ->with('Mastodon access token not found. Skipping crawling of bat signal.')
        ;

        $this->entityManager->expects($this->never())->method('persist');

        $crawler->crawl();
    }

    public function testInvalidResponse(): void
    {
        $response = $this->createResponse([], 400);
        $this->httpClient->expects($this->once())->method('get')
            ->willReturn($response)
        ;

        $this->logger->expects($this->once())->method('critical')
            ->with('Failed to fetch messages from No Agenda Social.')
        ;

        $this->logger->expects($this->once())->method('debug')
            ->with('No bat signal found.')
        ;

        $this->entityManager->expects($this->never())->method('persist');

        $this->crawler->crawl();
    }

    public function testNoSignal(): void
    {
        $response = $this->createResponse([]);
        $this->httpClient->expects($this->once())->method('get')
            ->willReturn($response)
        ;

        $this->logger->expects($this->once())->method('debug')
            ->with('No bat signal found.')
        ;

        $this->entityManager->expects($this->never())->method('persist');

        $this->crawler->crawl();
    }

    public function testSignalExists(): void
    {
        $response = $this->createResponse([[
            'content' => 'We\'re live now at noagendastream.com/ with No Agenda episode 33 #@pocketnoagenda',
            'created_at' => '2022-02-02 12:00:00',
        ]]);
        $this->httpClient->expects($this->once())->method('get')
            ->willReturn($response)
        ;

        $this->repository->expects($this->once())->method('exists')
            ->willReturn(true)
        ;

        $this->logger->expects($this->once())->method('debug')
            ->with('Found bat signal already exists.')
        ;

        $this->entityManager->expects($this->never())->method('persist');

        $this->crawler->crawl();
    }

    private function createResponse(array $body, int $statusCode = 200): ResponseInterface
    {
        $httpFactory = new Psr17Factory();
        $stream = $httpFactory->createStream(json_encode($body));
        $response = $httpFactory->createResponse($statusCode)->withBody($stream);

        $stream->rewind();

        return $response;
    }
}
