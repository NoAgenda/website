<?php

namespace App\Message;

class MergeUser
{
    public function __construct(
        public readonly int $id,
    ) {}
}
