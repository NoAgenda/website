<?php

namespace App\EventListener;

use App\Message\Crawl;
use App\Message\GenerateEpisodeReport;
use App\Message\PrepareEpisode;
use App\Message\PublishEpisode;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use function Symfony\Component\String\u;

class MessengerListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => 'onReceiveMessage',
            WorkerMessageFailedEvent::class => 'onFailedToHandleMessage',
        ];
    }

    public function __construct(
        private LoggerInterface $crawlerLogger,
    ) {}

    public function onReceiveMessage(WorkerMessageReceivedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof Crawl) {
            $log = sprintf('Crawling %s', u($message->data)->folded());

            if ($message->episodeCode) {
                $log .= sprintf(' for episode %s', $message->episodeCode);
            }
        } elseif ($message instanceof GenerateEpisodeReport) {
            $log = sprintf('Sending episode report for episode %s', $message->episodeCode);
        } elseif ($message instanceof PrepareEpisode) {
            $log = sprintf('Preparing the crawler for episode %s', $message->episodeCode);
        } elseif ($message instanceof PublishEpisode) {
            $log = sprintf('Publishing episode %s', $message->episodeCode);
        } else {
            $job = u(get_class($message))->replace('App\\Message\\', '')->folded();
            $log = sprintf('Executing job "%s"', $job);
        }

        $this->crawlerLogger->info($log);
    }

    public function onFailedToHandleMessage(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof Crawl) {
            $log = sprintf('Failed to crawl %s', u($message->data)->folded());

            if ($message->episodeCode) {
                $log .= sprintf(' for episode %s', $message->episodeCode);
            }
        } else {
            $job = u(get_class($message))->replace('App\\Message\\', '')->folded();
            $log = sprintf('Execution of job "%s" failed', $job);

            if ($throwable = $event->getThrowable()) {
                $log .= sprintf(' (reason: %s)', $throwable->getMessage());
            }
        }

        $this->crawlerLogger->error($log);
    }
}
