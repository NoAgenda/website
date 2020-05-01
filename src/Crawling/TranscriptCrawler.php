<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Message\CrawlEpisodeTranscript;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClient;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\MessageBusInterface;

class TranscriptCrawler
{
    use LoggerAwareTrait;

    private $entityManager;
    private $httpClient;
    private $messenger;

    public function __construct(HttpMethodsClient $httpClient, EntityManagerInterface $entityManager, MessageBusInterface $messenger)
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->messenger = $messenger;
        $this->logger = new NullLogger();
    }

    public function crawl(): void
    {
        $episodeRepository = $this->entityManager->getRepository(Episode::class);

        foreach ($this->crawlSitePages() as $code => $transcriptUri) {
            $episode = $episodeRepository->findOneByCode($code);

            if ($episode && $transcriptUri !== $episode->getTranscriptUri()) {
                $episode->setTranscriptUri($transcriptUri);

                $this->entityManager->persist($episode);

                $this->logger->info("Found new transcripts for episode $code.");

                $crawlTranscriptMessage = new CrawlEpisodeTranscript($episode->getCode());
                $this->messenger->dispatch($crawlTranscriptMessage);
            }
        }
    }

    public function crawlEpisode(Episode $episode): void
    {
        if (!$episode->getTranscriptUri()) {
            throw new \Exception('No transcript URI found for episode.');
        }

        $rawLines = $this->crawlTranscript($episode->getTranscriptUri());
        $lines = [];

        foreach ($rawLines as $rawLine) {
            $lines[] = [
                'timestamp' => $rawLine['timestamp'],
                'text' => $rawLine['text'],
            ];
        }

        $transcriptPath = sprintf('%s/transcripts/%s.json', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

        file_put_contents($transcriptPath, json_encode($lines));

        $episode->setTranscript(true);

        $this->entityManager->persist($episode);

        $this->logger->info(sprintf('Crawled %s transcript lines for episode %s.', count($rawLines), $episode->getCode()));
    }

    private function crawlSitePages(bool $allPages = false): array
    {
        $output = [];

        $pageUri = 'https://natranscript.online/tr/page/{key}/';
        $page = 1;

        do {
            $response = $this->httpClient->get(str_replace('{key}', $page, $pageUri));

            if ($response->getStatusCode() !== 200) {
                break;
            }

            $data = $response->getBody()->getContents();

            $articleDefinitions = $this->matchHtmlTags($data, 'article');

            foreach ($articleDefinitions as $definition) {
                $articleUri = $this->matchArticleUri($definition);

                if (!$articleUri) {
                    continue;
                }

                $articleResponse = $this->httpClient->get($articleUri);
                $articleData = $articleResponse->getBody()->getContents();

                $uri = $this->matchTranscriptUri($articleData);

                if (!$uri) {
                    continue;
                }

                // Match the episode code in the filename
                preg_match("#/(\d+?)-transcript#", $uri, $matches);

                if (!$matches[1]) {
                    continue;
                }

                $output[$matches[1]] = $uri;

                ++$page;
            }
        }
        while ($allPages);

        return $output;
    }

    private function crawlTranscript(string $uri): array
    {
        $response = $this->httpClient->get($uri);
        $data = $response->getBody()->getContents();

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

            if (strpos($line, "target='naplayer'") !== false) {
                preg_match("#^<a target='naplayer' title='click to play' href='http://naplay.it/([^/]+)/([0-9\-]+)'>([^<]+)</a>(.+)$#", $line, $matches);
            }
            elseif (strpos($line, "target='yt'") !== false) {
                preg_match("#^<a target='yt' title='click to play' href='https://youtu.be/([^?]+)\?t=([^s]+)s'>([^<]+)</a>(.+)$#", $line, $matches);
            }
            elseif (strpos($line, "youtu.be") !== false) {
                preg_match("#^<a\s+href='https://youtu.be/([^?]+)\?t=([^s]+)s'>([^<]+)</a>(.+)$#", $line, $matches);
            }
            else {
                $matches = [];
            }

            if (count($matches) < 5) {
                $output['invalidLines'][] = $line;

                $matches = [null, null, $lastTimestamp + 1, htmlspecialchars($line), ''];
            }

            list(, , $rawTimestamp, $firstText, $lastText) = $matches;

            $timestamp = $this->parseTimestamp($rawTimestamp);

            $output['lines'][] = [
                'text' => implode('', [$firstText, $lastText]),
                'timestamp' => $timestamp,
                'source' => $uri,
            ];

            // Hold onto last timestamp in case the next line doesn't have one
            $lastTimestamp = $timestamp;
        }

        return $output['lines'];
    }

    private function matchHtmlTags(string $page, string $tagname): array
    {
        $pattern = "#<\s*?$tagname\b[^>]*>(.*?)</$tagname\b[^>]*>#s";
        preg_match_all($pattern, $page, $matches);

        return $matches[0];
    }

    private function matchArticleUri(string $definition): ?string
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

    private function parseTimestamp($raw): int
    {
        if (strpos($raw, '-')) {
            list($hours, $minutes, $seconds) = explode('-', $raw);

            $timestamp = $seconds;
            $timestamp += $minutes * 60;
            $timestamp += $hours * 60 * 60;

            return $timestamp;
        }

        if (strpos($raw, 'h')) {
            list($hours, $minutes, $seconds) = preg_split('/[a-z]/i', $raw);

            $timestamp = $seconds;
            $timestamp += $minutes * 60;
            $timestamp += $hours * 60 * 60;

            return $timestamp;
        }

        return (int) $raw;
    }
}
