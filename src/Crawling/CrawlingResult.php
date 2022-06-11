<?php

namespace App\Crawling;

class CrawlingResult
{
    public function __construct(
        public readonly bool $success = true,
        public readonly ?\Throwable $exception = null,
    ) {}
}
