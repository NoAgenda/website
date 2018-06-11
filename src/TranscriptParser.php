<?php

namespace App;

class TranscriptParser
{
    public function crawl(bool $allPages = false): array
    {
        $output = [];

        $pageUri = 'https://natranscript.online/tr/page/{key}/';
        $page = 1;

        do {
            $data = file_get_contents(str_replace('{key}', $page, $pageUri));

            $pageExists = in_array('HTTP/1.1 200 OK', $http_response_header);

            if (!$pageExists) {
                break;
            }

            $articleDefinitions = $this->matchHtmlTags($data, 'article');

            foreach ($articleDefinitions as $definition) {
                $articleUri = $this->matchShowUri($definition);

                if (!$articleUri) {
                    continue;
                }

                $articleData = file_get_contents($articleUri);

                $uri = $this->matchTranscriptUri($articleData);

                if (!$uri) {
                    continue;
                }

                // Match the show code in the filename
                preg_match("#\/(\d+?)-transcript#", $uri, $matches);

                if (!$matches[1]) {
                    continue;
                }

                $output[$matches[1]] = $uri;
            }
        }
        while ($pageExists && $allPages);

        return $output;
    }

    public function parse($uri): array
    {
        $data = file_get_contents($uri);
        $root = new \SimpleXMLElement($data);

        $output = [
            'lines' => [],
            'invalidLines' => [],
        ];

        $lastTimestamp = 0;

        foreach ($root->body->outline->outline as $outline) {
            $line = (string) $outline['text'];

            if ($line == '') {
                continue;
            }

            preg_match("#^<a target='naplayer' title='click to play' href='http://naplay.it/([^/]+)/([0-9\-]+)'>([^<]+)</a>(.+)$#", $line, $matches);

            if (count($matches) < 5) {
                $output['invalidLines'][] = $line;

                $matches = [null, null, $lastTimestamp + 1, htmlspecialchars($line), ''];
            }

            list(, $showCode, $rawTimestamp, $firstText, $lastText) = $matches;

            $timestamp = $this->parseTimestamp($rawTimestamp);

            $output['lines'][] = [
                'text' => implode('', [$firstText, $lastText]),
                'timestamp' => $timestamp,
                'source' => $uri,
            ];

            // Hold onto last timestamp in case the next line doesn't have one
            $lastTimestamp = $timestamp;
        }

        return $output;
    }

    private function parseTimestamp($raw): int
    {
        if (!strpos($raw, '-')) {
            return (int) $raw;
        }

        list($hours, $minutes, $seconds) = explode('-', $raw);

        $timestamp = $seconds;
        $timestamp += $minutes * 60;
        $timestamp += $hours * 60 * 60;

        return $timestamp;
    }

    function matchHtmlTags(string $page, string $tagname)
    {
        $pattern = "#<\s*?$tagname\b[^>]*>(.*?)</$tagname\b[^>]*>#s";
        preg_match_all($pattern, $page, $matches);

        return $matches[0];
    }

    private function matchShowUri(string $definition): ?string
    {
        preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $definition, $matches);

        $validMatches = array_filter($matches[0], function($uri) {
            preg_match("/no-agenda-episode-(\d+)-/", $uri, $matches);

            // Only return if the link describes an episode
            return count($matches);
        });

        return count($validMatches) ? array_values($validMatches)[0] : null;
    }

    private function matchTranscriptUri(string $data): ?string
    {
        preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $data, $matches);

        $validMatches = array_filter($matches[0], function($uri) {
            preg_match("/\.opml$/", $uri, $matches);

            // Only return if the link describes an opml-file
            return count($matches);
        });

        return count($validMatches) ? array_values($validMatches)[0] : null;
    }
}
