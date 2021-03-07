<?php

namespace App\Crawling;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

class CrawlingLogger extends AbstractLogger
{
    private $notifier;

    private $loggers = [];

    public function __construct(NotifierInterface $notifier)
    {
        $this->notifier = $notifier;

        $logPath = sprintf('%s/crawler_logs', $_SERVER['APP_STORAGE_PATH']);

        if (!is_dir($logPath)) {
            $filesystem = new Filesystem();
            $filesystem->mkdir($logPath);
        }
    }

    public function addLogger(LoggerInterface $logger): self
    {
        $this->loggers[] = $logger;

        return $this;
    }

    public function log($level, $message, array $context = []): void
    {
        foreach ($this->loggers as $logger) {
            /** @var LoggerInterface $logger */
            $logger->log($level, $message, $context);
        }

        $log = sprintf('%s    %s %s', (new \DateTime())->format('H:i:s'), str_pad(strtoupper($level), 12, ' '), $message);

        if ('debug' !== $level) {
            file_put_contents($this->getLogPath(), $log . "\n", FILE_APPEND | LOCK_EX);
        }

        if (in_array($level, ['emergency', 'alert', 'critical', 'error', 'warning'])) {
            $notification = new Notification($message, ['chat/slack_default']);
            $this->notifier->send($notification);
        }
    }

    private function getLogPath(): string
    {
        return sprintf('%s/crawler_logs/%s.log', $_SERVER['APP_STORAGE_PATH'], (new \DateTime())->format('Ymd'));
    }
}
