<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
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

    public function __construct(
        private LoggerInterface $crawlerLogger,
    ) {}

    public function onReceiveMessage(WorkerMessageReceivedEvent $event): void
    {
        $this->crawlerLogger->info(sprintf('Executing job: %s', get_class($event->getEnvelope()->getMessage())));
    }

    public function onHandledMessage(WorkerMessageHandledEvent $event): void
    {
        // $this->$this->crawlerLogger->info(sprintf('Finished job: %s', get_class($event->getEnvelope()->getMessage())));
    }

    public function onFailedToHandleMessage(WorkerMessageFailedEvent $event): void
    {
        $this->crawlerLogger->error(sprintf('Job failed: %s', get_class($event->getEnvelope()->getMessage())));
    }
}
