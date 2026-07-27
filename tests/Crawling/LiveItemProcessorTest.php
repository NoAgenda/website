<?php

namespace App\Tests\Crawling;

use App\Crawling\LiveItemProcessor;
use App\Crawling\NotificationPublisher;
use App\Entity\BatSignal;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\NotifierInterface;

class LiveItemProcessorTest extends TestCase
{
    public function testPublishesLiveItemOnce(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $publisher = $this->getMockBuilder(NotificationPublisher::class)->disableOriginalConstructor()->getMock();
        $repository = $this->getMockBuilder(BatSignalRepository::class)->disableOriginalConstructor()->getMock();
        $notifier = $this->createMock(NotifierInterface::class);
        $processor = new LiveItemProcessor($entityManager, $publisher, $repository, $notifier);

        $repository->expects($this->once())->method('exists')->willReturn(false);
        $publisher->expects($this->once())->method('publishMastodonLiveAnnouncement')
            ->with('No Agenda Episode 1889 Live', 'https://example.com/live', 'https://example.com/1889.jpg');
        $publisher->expects($this->once())->method('sendUserLiveNotifications')
            ->with('No Agenda Episode 1889 Live', 'https://example.com/live');
        $entityManager->expects($this->once())->method('persist')
            ->with($this->callback(fn (BatSignal $signal) => 'show-1889' === $signal->getCode()
                && '2026-07-26T17:55:00+00:00' === $signal->getDeployedAt()->format(DATE_ATOM)));
        $notifier->expects($this->once())->method('send');

        $processor->process($this->feed('live'));
    }

    public function testIgnoresPendingLiveItem(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $publisher = $this->getMockBuilder(NotificationPublisher::class)->disableOriginalConstructor()->getMock();
        $repository = $this->getMockBuilder(BatSignalRepository::class)->disableOriginalConstructor()->getMock();
        $notifier = $this->createMock(NotifierInterface::class);
        $processor = new LiveItemProcessor($entityManager, $publisher, $repository, $notifier);

        $repository->expects($this->never())->method('exists');
        $publisher->expects($this->never())->method('publishMastodonLiveAnnouncement');
        $publisher->expects($this->never())->method('sendUserLiveNotifications');
        $entityManager->expects($this->never())->method('persist');

        $processor->process($this->feed('pending'));
    }

    private function feed(string $status): string
    {
        return <<<XML
            <rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
                 xmlns:podcast="https://podcastindex.org/namespace/1.0">
              <channel>
                <podcast:liveItem status="$status" start="2026-07-26T17:55:00+00:00">
                  <title>No Agenda Episode 1889 Live</title>
                  <guid>show-1889</guid>
                  <itunes:image href="https://example.com/1889.jpg" />
                  <podcast:contentLink href="https://example.com/live">Listen live</podcast:contentLink>
                </podcast:liveItem>
              </channel>
            </rss>
            XML;
    }
}
