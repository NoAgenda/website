<?php

namespace App\Crawling;

use App\Entity\BatSignal;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

class LiveItemProcessor
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationPublisher $notificationPublisher,
        private readonly BatSignalRepository $batSignalRepository,
        private readonly NotifierInterface $notifier,
    ) {
        $this->logger = new NullLogger();
    }

    public function process(string $feed): void
    {
        $document = new \DOMDocument();
        $document->loadXML($feed);

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $xpath->registerNamespace('podcast', 'https://podcastindex.org/namespace/1.0');

        foreach ($xpath->query('/rss/channel/podcast:liveItem[@status="live"]') as $liveItem) {
            $guid = trim($xpath->evaluate('string(guid)', $liveItem));
            $title = trim($xpath->evaluate('string(title)', $liveItem));
            $uri = trim($xpath->evaluate('string(link)', $liveItem))
                ?: trim($xpath->evaluate('string(podcast:contentLink/@href)', $liveItem));
            $imageUri = trim($xpath->evaluate('string(itunes:image/@href)', $liveItem));
            $start = $liveItem->getAttribute('start');

            if (!$guid || !$title || !$uri || !$start) {
                $this->logger->warning('Ignoring an incomplete live item.');

                continue;
            }

            $code = strlen($guid) <= 16 ? $guid : substr(hash('sha256', $guid), 0, 16);

            $signal = (new BatSignal())
                ->setCode($code)
                ->setDeployedAt(new \DateTimeImmutable($start));

            if ($this->batSignalRepository->exists($signal)) {
                $this->logger->debug(sprintf('Live item "%s" already exists.', $guid));

                continue;
            }

            $this->logger->info(sprintf('Found live item "%s".', $guid));

            $this->notificationPublisher->publishMastodonLiveAnnouncement($title, $uri, $imageUri ?: null);
            $this->notificationPublisher->sendUserLiveNotifications($title, $uri);
            $this->entityManager->persist($signal);

            $host = ucfirst($_SERVER['DEPLOYMENT_HOST'] ?? gethostname());
            $this->notifier->send(new Notification(sprintf('%s: live item has been published.', $host)));
        }
    }
}
