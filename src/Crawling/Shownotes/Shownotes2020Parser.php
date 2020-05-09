<?php

namespace App\Crawling\Shownotes;

use App\Entity\Episode;
use vipnytt\OPMLParser;

class Shownotes2020Parser implements ShownotesParserInterface
{
    private $episode;
    private $contents;

    public function __construct(Episode $episode)
    {
        $this->episode = $episode;

        $path = ShownotesParserFactory::getShownotesPath($episode);
        $xml = file_get_contents($path);
        $this->contents = (new OPMLParser($xml))->getResult();
    }

    public static function supports(Episode $episode): bool
    {
        return file_exists(ShownotesParserFactory::getShownotesPath($episode));
    }

    public function getCredits(): array
    {
        $credits = [];

        foreach ($this->getTab('Credits') as $node) {
            $node['text'] = $node['text'] ?? '';
            $node['text'] = trim($node['text']);

            if ($node['text'] === 'Executive Producers:') {
                foreach ($node['@outlines'] as $producerNode) {
                    $credits['Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 20) === 'Executive Producers:') {
                $credits['Executive Producers'] = substr($node['text'], 20);
            } else if ($node['text'] === 'Executive Producer:') {
                foreach ($node['@outlines'] as $producerNode) {
                    $credits['Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 19) === 'Executive Producer:') {
                $credits['Executive Producers'] = substr($node['text'], 19);
            } else if ($node['text'] === 'Associate Executive Producers:') {
                foreach ($node['@outlines'] as $producerNode) {
                    $credits['Associate Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 30) === 'Associate Executive Producers:') {
                $credits['Associate Executive Producers'] = substr($node['text'], 30);
            } else if ($node['text'] === 'Associate Executive Producer:') {
                foreach ($node['@outlines'] as $producerNode) {
                    $credits['Associate Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 29) === 'Associate Executive Producer:') {
                $credits['Associate Executive Producers'] = substr($node['text'], 29);
            } else if (substr($node['text'], 0, 7) === 'Art By:') {
                $credits['Cover Artist'] = substr($node['text'], 7);
            }
        }

        foreach ($credits as $key => $credit) {
            if (is_array($credit)) {
                $credits[$key] = array_map('trim', $credit);
            } else {
                $credits[$key] = trim($credit);
            }
        }

        return $credits;
    }

    public function getShownotes(): array
    {
        return $this->getTab('Shownotes');
    }

    private function getTabs(): array
    {
        foreach ($this->contents['body'] as $node) {
            $node['type'] = $node['type'] ?? 'text';

            if ('tabs' === $node['type']) {
                return $node['@outlines'];
            }
        }

        throw new \LogicException(sprintf('Unable to find tabs in shownotes for episode %s.', $this->episode->getCode()));
    }

    private function getTab(string $tab): array
    {
        foreach ($this->getTabs() as $node) {
            if ($node['text'] === $tab) {
                return $node['@outlines'];
            }
        }

        throw new \LogicException(sprintf('Invalid tab "%s" requested in shownotes for episode %s.', $tab, $this->episode->getCode()));
    }
}
