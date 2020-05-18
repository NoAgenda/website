<?php

namespace App\Crawling;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class CrawlingLogger extends AbstractLogger
{
    private $loggers = [];

    public function __construct()
    {
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
    }

    private function getLogPath(): string
    {
        return sprintf('%s/crawler_logs/%s.log', $_SERVER['APP_STORAGE_PATH'], (new \DateTime())->format('Ymd'));
    }
}
