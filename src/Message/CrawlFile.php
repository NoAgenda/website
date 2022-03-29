<?php

namespace App\Message;

class CrawlFile
{
    public readonly \DateTime $initializedAt;

    public function __construct(
        public readonly string $crawler,
        public readonly string $episodeCode,
        public readonly ?\DateTime $lastModifiedAt = null,
        \DateTime $initializedAt = null,
    ) {
        $this->initializedAt = $initializedAt ?? new \DateTime();
    }
}
