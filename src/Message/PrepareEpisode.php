<?php

namespace App\Message;

class PrepareEpisode
{
    public function __construct(
        public readonly string $episodeCode,
    ) {}

    public function __toString(): string
    {
        return "Prepare episode $this->episodeCode";
    }
}
