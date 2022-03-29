<?php

namespace App\Message;

class Crawl
{
    public function __construct(
        public readonly string $crawler,
        public readonly ?string $episodeCode = null,
    ) {}
}
