<?php

namespace App\Crawling\Shownotes;

use App\Entity\Episode;

class ShownotesParserFactory
{
    private $parsers;

    public function __construct()
    {
        $this->parsers = [
            Shownotes2020Parser::class,
        ];
    }

    public function get(Episode $episode): ?ShownotesParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser::supports($episode)) {
                return new $parser($episode);
            }
        }

        return null;
    }

    public static function getShownotesPath(Episode $episode): string
    {
        return sprintf('%s/shownotes/%s.xml', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
    }
}
