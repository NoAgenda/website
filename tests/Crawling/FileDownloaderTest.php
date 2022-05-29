<?php

namespace App\Tests\Crawling;

use App\Crawling\FileDownloader;
use App\Exception\FileDownloadException;
use App\Repository\ScheduledFileDownloadRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
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
        $this->httpClient = new MockHttpClient();
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
        $this->httpClient->setResponseFactory([
            $this->createResponse(),
        ]);

        $this->logger->expects($this->once())->method('info')
            ->with('File "https://www.example.com/foo" will be (re)downloaded. Last modified at 2022-02-02 02:22:20.')
        ;

        $this->downloader->download($this->uri, $this->path);

        $this->assertTrue(file_exists($this->path));
        $this->assertEquals('itm', file_get_contents($this->path));
    }

    public function testHttpModified(): void
    {
        $this->httpClient->setResponseFactory([
            $this->createResponse(),
        ]);

        $this->logger->expects($this->once())->method('info')
            ->with('File "https://www.example.com/foo" was changed. Last modified at 2022-02-02 02:22:20.')
        ;

        $this->downloader->download($this->uri, $this->path, new \DateTime('2022-02-01 02:22:20'));

        $this->assertTrue(file_exists($this->path));
        $this->assertEquals('itm', file_get_contents($this->path));
    }

    public function testHttpNotModified(): void
    {
        $this->httpClient->setResponseFactory([
            $this->createResponse(304),
        ]);

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
        $this->httpClient->setResponseFactory([
            $this->createResponse(404),
        ]);

        $this->expectException(FileDownloadException::class);

        $this->downloader->download($this->uri, $this->path);

        $this->assertFalse(file_exists($this->path));
    }

    private function createResponse(int $statusCode = 200): MockResponse
    {
        return new MockResponse('itm', [
            'http_code' => $statusCode,
            'response_headers' => [
                'Last-Modified' => (new \DateTime('2022-02-02 02:22:20'))->format('D M d Y H:i:s O'),
            ],
        ]);
    }
}
