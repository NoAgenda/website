<?php

namespace App\Message;

class GenerateEpisodeReport
{
    public function __construct(
        public readonly string $code,
    ) {}
}
