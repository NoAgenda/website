<?php

namespace App\Crawling\Shownotes;

use App\Entity\Episode;
use vipnytt\OPMLParser;

class Shownotes612Parser implements ShownotesParserInterface
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
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 20) === 'Executive Producers:') {
                $credits['Executive Producers'] = substr($node['text'], 20);
            } else if ($node['text'] === 'Executive Producer:') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 19) === 'Executive Producer:') {
                $credits['Executive Producers'] = substr($node['text'], 19);
            } else if ($node['text'] === 'Associate Executive Producers:' || $node['text'] === 'Associate Executive Producers') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Associate Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 30) === 'Associate Executive Producers:') {
                $credits['Associate Executive Producers'] = substr($node['text'], 30);
            } else if ($node['text'] === 'Associate Executive Producer:') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Associate Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 29) === 'Associate Executive Producer:') {
                $credits['Associate Executive Producers'] = substr($node['text'], 29);
            } else if ($node['text'] === 'Special Executive Producers:' || $node['text'] === 'Special Executive Producers') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Special Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 28) === 'Special Executive Producers:') {
                $credits['Special Executive Producers'] = substr($node['text'], 28);
            } else if ($node['text'] === 'Special Executive Producer:') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Special Executive Producers'][] = $producerNode['text'];
                }
            } else if (substr($node['text'], 0, 27) === 'Special Executive Producer:') {
                $credits['Special Executive Producers'] = substr($node['text'], 27);
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
        foreach ($this->getTabs() as $node) {
            if ($node['text'] === 'Shownotes') {
                return $this->parseOutlines($node);
            }
        }

        return [];
    }

    public function getClips(): array
    {
        $clipsTab = null;

        foreach ($this->getTabs() as $node) {
            if ($node['text'] === 'Clips and Docs') {
                $clipsTab = $this->parseOutlines($node);
            } else if ($node['text'] === 'Clips and Stuff') {
                $clipsTab = $this->parseOutlines($node);
            }
        }

        if (!$clipsTab) {
            return [];
        }

        $clipsNode = null;

        $clips = [];

        foreach ($clipsTab as $collectionNode) {
            $category = $collectionNode['text'];

            foreach ($this->parseOutlines($collectionNode) as $node) {
                $clip = $this->parseClipNode($node);

                if ($clip) {
                    $clips[$category][] = $clip;
                }

                foreach ($this->parseOutlines($node) as $childNode) {
                    $childClip = $this->parseClipNode($childNode);

                    if ($childClip) {
                        $clips[$category][] = $childClip;
                    }
                }
            }
        }

        return $clips;
    }

    private function getTabs(): array
    {
        foreach ($this->contents['body'] as $node) {
            $node['type'] = $node['type'] ?? 'text';

            if ('tabs' === $node['type']) {
                return $this->parseOutlines($node);
            }
        }

        throw new \LogicException(sprintf('Unable to find tabs in shownotes for episode %s.', $this->episode->getCode()));
    }

    private function getTab(string $tab, $canBeEmpty = false): array
    {
        foreach ($this->getTabs() as $node) {
            if ($node['text'] === $tab) {
                return $this->parseOutlines($node);
            }
        }

        throw new \LogicException(sprintf('Invalid tab "%s" requested in shownotes for episode %s.', $tab, $this->episode->getCode()));
    }

    private function parseOutlines(?array $node): array
    {
        if (!isset($node['@outlines'])) {
            return [];
        }

        return $node['@outlines'];
    }

    private function parseClipNode(array $node): ?array
    {
        $type = $node['type'] ?? (isset($node['url']) ? 'link' : 'text');
        $title = trim(strip_tags($node['text']));

        if ($type === 'link' && preg_match('/^.*\.(mp3|mp4|m4a|3gp|ogg|wma|webm)$/i', $title)) {
            $type = 'audio';
        }

        if ($type === 'image' && $title == '') {
            $title = 'Image';
        }

        if ($type === 'text' && $title == '') {
            if (str_contains($node['text'], '<img')) {
                $matches = array();
                preg_match('/src="([^"]+)"/', $node['text'], $matches);

                if (isset($matches[1])) {
                    return [
                        'type' => 'image',
                        'title' => 'Image',
                        'uri' => $matches[1],
                    ];
                }
            }

            return null;
        }

        return [
            'type' => $type,
            'title' => $title,
            'uri' => $node['url'] ?? false,
        ];
    }
}
