<?php

namespace App\Tests\Crawling;

use App\Crawling\FileDownloader;
use App\Exception\FileDownloadException;
use App\Repository\ScheduledFileDownloadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClientInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;

class FileDownloaderTest extends TestCase
{
    private $downloader;
    private $httpClient;
    private $logger;

    private $uri;
    private $path;

    public function setUp(): void
    {
        $entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $repository = $this->getMockBuilder(ScheduledFileDownloadRepository::class)->disableOriginalConstructor()->getMock();
        $this->httpClient = $this->getMockBuilder(HttpMethodsClientInterface::class)->getMock();
        $messenger = $this->getMockBuilder(MessageBusInterface::class)->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->downloader = new FileDownloader($entityManager, $repository, $this->httpClient, $messenger, []);
        $this->downloader->setLogger($this->logger);

        $this->uri = 'https://www.example.com/foo';
        $this->path = sprintf('%s/dumpfile', __DIR__);
    }

    public function tearDown(): void
    {
        (new Filesystem())->remove($this->path);
    }

    public function testHttpDownload(): void
    {
        $response = $this->createResponse();
        $this->httpClient->expects($this->once())->method('get')
            ->willReturn($response)
        ;

        $this->logger->expects($this->once())->method('info')
            ->with('File "https://www.example.com/foo" has been (re)downloaded. Last modified at 2022-02-02 02:22:20.')
        ;

        $this->downloader->download($this->uri, $this->path);

        $this->assertTrue(file_exists($this->path));
        $this->assertEquals('itm', file_get_contents($this->path));
    }

    public function testHttpModified(): void
    {
        $response = $this->createResponse();
        $this->httpClient->expects($this->once())->method('get')
            ->willReturn($response)
        ;

        $this->logger->expects($this->once())->method('info')
            ->with('File "https://www.example.com/foo" was changed. Last modified at 2022-02-02 02:22:20.')
        ;

        $this->downloader->download($this->uri, $this->path, new \DateTime('2022-02-01 02:22:20'));

        $this->assertTrue(file_exists($this->path));
        $this->assertEquals('itm', file_get_contents($this->path));
    }

    public function testHttpNotModified(): void
    {
        $response = $this->createResponse(304);
        $this->httpClient->expects($this->once())->method('get')
            ->willReturn($response)
        ;

        $this->logger->expects($this->once())->method('debug')
            ->with('No changes to file "https://www.example.com/foo". Last modified at 2022-02-02 02:22:20.')
        ;

        $ifModifiedSince = new \DateTime('2022-02-02 02:22:20');
        $lastModifiedAt = $this->downloader->download($this->uri, $this->path, $ifModifiedSince);

        $this->assertEquals($ifModifiedSince, $lastModifiedAt);
        $this->assertFalse(file_exists($this->path));
    }

    public function testHttpFailed(): void
    {
        $this->httpClient->expects($this->once())->method('get')
            ->willThrowException(new \Exception())
        ;

        $this->expectException(FileDownloadException::class);

        $this->downloader->download($this->uri, $this->path);

        $this->assertFalse(file_exists($this->path));
    }

    private function createResponse(int $statusCode = 200): ResponseInterface
    {
        $httpFactory = new Psr17Factory();
        $stream = $httpFactory->createStream('itm');
        $response = $httpFactory->createResponse($statusCode)
            ->withHeader('Last-Modified', (new \DateTime('2022-02-02 02:22:20'))->format('D M d Y H:i:s O'))
            ->withBody($stream)
        ;

        $stream->rewind();

        return $response;
    }
}
