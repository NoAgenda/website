<?php

namespace App\Message;

class Crawl
{
    public readonly \DateTime $initializedAt;

    public function __construct(
        public readonly string $data,
        public readonly ?string $episodeCode,
        public readonly ?\DateTime $lastModifiedAt = null,
        \DateTime $initializedAt = null,
    ) {
        $this->initializedAt = $initializedAt ?? new \DateTime();
    }

    public function __toString(): string
    {
        if ($this->episodeCode) {
            return "Crawl $this->data for episode $this->episodeCode";
        }

        return "Crawl $this->data";
    }
}
