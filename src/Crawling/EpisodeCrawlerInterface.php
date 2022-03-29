<?php

namespace App\Crawling;

use App\Entity\Episode;

interface EpisodeCrawlerInterface
{
    public function crawl(Episode $episode): void;
}
