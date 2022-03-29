<?php

namespace App\Message;

class PrepareEpisode
{
    public function __construct(
        public readonly string $code,
    ) {}
}
