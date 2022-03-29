<?php

namespace App\Message;

class PublishEpisode
{
    public function __construct(
        public readonly string $code,
    ) {}
}
