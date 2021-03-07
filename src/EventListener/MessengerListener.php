<?php
/*
 * (c) Tim Goudriaan <tim@codedmonkey.com>
 */

namespace App\EventListener;

use App\Crawling\CrawlingLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

class MessengerListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => 'onReceiveMessage',
            WorkerMessageHandledEvent::class => 'onHandledMessage',
            WorkerMessageFailedEvent::class => 'onFailedToHandleMessage',
        ];
    }

    private $logger;

    public function __construct(CrawlingLogger $logger)
    {
        $this->logger = $logger;
    }

    public function onReceiveMessage(WorkerMessageReceivedEvent $event): void
    {
        $this->logger->info(sprintf('Executing job: %s', get_class($event->getEnvelope()->getMessage())));
    }

    public function onHandledMessage(WorkerMessageHandledEvent $event): void
    {
        // $this->logger->info(sprintf('Finished job: %s', get_class($event->getEnvelope()->getMessage())));
    }

    public function onFailedToHandleMessage(WorkerMessageFailedEvent $event): void
    {
        $this->logger->error(sprintf('Job failed: %s', get_class($event->getEnvelope()->getMessage())));
    }
}
