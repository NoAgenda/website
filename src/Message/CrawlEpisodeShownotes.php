<?php

namespace App\Message;

class CrawlEpisodeShownotes
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
