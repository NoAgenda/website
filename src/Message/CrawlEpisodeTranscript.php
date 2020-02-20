<?php

namespace App\Message;

class CrawlEpisodeTranscript
{
    private $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
