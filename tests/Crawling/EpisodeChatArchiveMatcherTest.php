<?php

namespace App\Tests\Crawling;

use App\Crawling\EpisodeChatArchiveMatcher;
use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class EpisodeChatArchiveMatcherTest extends TestCase
{
    private $crawler;
    private $entityManager;
    private $logger;

    private $archivePath;

    public function setUp(): void
    {
        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->crawler = new EpisodeChatArchiveMatcher($this->entityManager);
        $this->crawler->setLogger($this->logger);

        $this->archivePath = sprintf('%s/chat_archives/33test.json', $_SERVER['APP_STORAGE_PATH']);
    }

    public function tearDown(): void
    {
        (new Filesystem())->remove($this->archivePath);
    }

    public function testMatchFound(): void
    {
        $episode = (new Episode())
            ->setCode('33test')
            ->setDuration(600)
            ->setRecordedAt(new \DateTime('2022-02-02 16:00:00'))
        ;

        $this->logger->expects($this->once())->method('info')
            ->with('Matched 1 chat messages for episode 33test.')
        ;

        $this->entityManager->expects($this->once())->method('persist');

        $this->crawler->crawl($episode);

        $this->assertEquals($this->archivePath, $episode->getChatArchivePath());
        $this->assertTrue(file_exists($this->archivePath));

        $chatArchive = json_decode(file_get_contents($this->archivePath), true);
        $this->assertEquals([[
            'username' => 'dudenamedben',
            'contents' => 'In The Morning!',
            'timestamp' => 61,
        ]], $chatArchive);
    }

    public function testNoRecordingTime(): void
    {
        $episode = (new Episode())
            ->setCode('33test')
        ;

        $this->logger->expects($this->once())->method('warning')
            ->with('Unable to match the chat archive for episode 33test because the recording time is unknown.')
        ;

        $this->crawler->crawl($episode);
    }

    public function testNoLogs(): void
    {
        $episode = (new Episode())
            ->setCode('33test')
            ->setDuration(600)
            ->setRecordedAt(new \DateTime('2033-02-02 16:00:00'))
        ;

        $this->logger->expects($this->once())->method('warning')
            ->with('No chat logs found for episode 33test.')
        ;

        $this->entityManager->expects($this->never())->method('persist');

        $this->crawler->crawl($episode);

        $this->assertNull($episode->getChatArchivePath());
        $this->assertFalse(file_exists($this->archivePath));
    }

    public function testMoMatch(): void
    {
        $episode = (new Episode())
            ->setCode('33test')
            ->setDuration(600)
            ->setRecordedAt(new \DateTime('2022-02-02 15:00:00'))
        ;

        $this->logger->expects($this->once())->method('warning')
            ->with('No chat messages found for episode 33test, even though chat logs are available.')
        ;

        $this->entityManager->expects($this->never())->method('persist');

        $this->crawler->crawl($episode);

        $this->assertNull($episode->getChatArchivePath());
        $this->assertFalse(file_exists($this->archivePath));
    }
}
