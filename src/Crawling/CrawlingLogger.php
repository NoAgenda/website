<?php

namespace App\Crawling;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class CrawlingLogger implements LoggerInterface
{
    use LoggerTrait;

    private bool $collecting = false;
    private array $collection = [];

    public function __construct(
        private LoggerInterface $monologLogger,
    ) {}

    public function log($level, $message, array $context = []): void
    {
        $this->monologLogger->log($level, $message, $context);

        if ($this->collecting) {
            $this->collection[] = [
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ];
        }
    }

    public function collect(): void
    {
        $this->collecting = true;
    }

    public function retrieve(): array
    {
        $collection = $this->collection;

        $this->collecting = false;
        $this->collection = [];

        return $collection;
    }
}
