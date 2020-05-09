<?php

namespace App\Crawling\Shownotes;

use App\Entity\Episode;

interface ShownotesParserInterface
{
    public function __construct(Episode $episode);
    public static function supports(Episode $episode): bool;

    public function getCredits(): array;
    public function getShownotes(): array;
}
