<?php

namespace App\Crawling;

use App\Entity\Episode;

interface EpisodeFileCrawlerInterface
{
    public function crawl(Episode $episode, \DateTime $ifModifiedSince = null): ?\DateTime;
}
