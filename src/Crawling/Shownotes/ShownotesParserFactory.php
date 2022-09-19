<?php

namespace App\Crawling\Shownotes;

use App\Entity\Episode;
use Psr\Log\LoggerInterface;

class ShownotesParserFactory
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function create(Episode $episode): ?ShownotesParser
    {
        return new ShownotesParser($episode, $this->logger);
    }
}
