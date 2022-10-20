<?php

namespace App\Crawling\Shownotes;

use App\Entity\Episode;
use Psr\Log\LoggerInterface;
use vipnytt\OPMLParser;

class ShownotesParser
{
    private ?array $tabs;

    public function __construct(
        private readonly Episode $episode,
        private readonly LoggerInterface $logger,
    ) {
        $this->tabs = $this->findTabs();
    }

    public function getClips(): array
    {
        $outlines = $this->getTab('Clips and Docs', 'Clips and Stuff');
        $clips = [];

        foreach ($outlines as $collectionOutline) {
            $category = $collectionOutline['text'];

            foreach ($this->parseOutlines($collectionOutline) as $clipOutline) {
                if ($clip = $this->parseClipOutline($clipOutline)) {
                    $clips[$category][] = $clip;
                }

                foreach ($this->parseOutlines($clipOutline) as $childOutline) {
                    if ($childClip = $this->parseClipOutline($childOutline)) {
                        $clips[$category][] = $childClip;
                    }
                }
            }
        }

        foreach ($clips as $category => $categoryClips) {
            usort($categoryClips, fn ($a, $b) => $a['sort_title'] <=> $b['sort_title']);

            $clips[$category] = $categoryClips;
        }

        return $clips;
    }

    public function getCredits(): array
    {
        $credits = [];

        foreach ($this->getTab('Credits') as $node) {
            $text = $node['text'];

            if ($text === 'Executive Producers:') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Executive Producers'][] = $producerNode['text'];
                }
            } elseif (str_starts_with($text, 'Executive Producers:')) {
                $credits['Executive Producers'][] = substr($text, 20);
            } elseif ($text === 'Executive Producer:') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Executive Producers'][] = $producerNode['text'];
                }
            } elseif (str_starts_with($text, 'Executive Producer:')) {
                $credits['Executive Producers'][] = substr($text, 19);
            } elseif ($text === 'Associate Executive Producers:' || $text === 'Associate Executive Producers') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Associate Executive Producers'][] = $producerNode['text'];
                }
            } elseif (str_starts_with($text, 'Associate Executive Producers:')) {
                $credits['Associate Executive Producers'][] = substr($text, 30);
            } elseif ($text === 'Associate Executive Producer:') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Associate Executive Producers'][] = $producerNode['text'];
                }
            } elseif (str_starts_with($text, 'Associate Executive Producer:')) {
                $credits['Associate Executive Producers'][] = substr($text, 29);
            } else if ($text === 'Special Executive Producers:' || $text === 'Special Executive Producers') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Special Executive Producers'][] = $producerNode['text'];
                }
            } elseif (str_starts_with($text, 'Special Executive Producers:')) {
                $credits['Special Executive Producers'][] = substr($text, 28);
            } elseif ($text === 'Special Executive Producer:') {
                foreach ($this->parseOutlines($node) as $producerNode) {
                    $credits['Special Executive Producers'][] = $producerNode['text'];
                }
            } elseif (str_starts_with($text, 'Special Executive Producer:')) {
                $credits['Special Executive Producers'][] = substr($text, 27);
            } elseif (str_starts_with($text, 'Art By:')) {
                $credits['Cover Artist'][] = substr($text, 7);
            }
        }

        foreach ($credits as $key => $credit) {
            $credits[$key] = array_map('trim', $credit);
        }

        return $credits;
    }

    public function getOutlines(): array
    {
        $outlines = $this->getTab('Shownotes');

        return array_filter($outlines, fn (array $outlines) => array_key_exists('@outlines', $outlines));
    }

    private function findTabs($parentNode = null): ?array
    {
        if (!$parentNode) {
            $xml = file_get_contents($this->episode->getShownotesPath());
            $contents = (new OPMLParser($xml))->getResult();

            $parentNode = $contents['body'];
        }

        foreach ($parentNode as $node) {
            $node['type'] = $node['type'] ?? 'text';

            if ('tabs' === $node['type']) {
                return $this->parseTabs($node);
            }
        }

        foreach ($parentNode as $node) {
            if ($tabs = $this->findTabs($this->parseOutlines($node))) {
                return $tabs;
            }
        }

        if (!$parentNode) {
            $this->logger->error(sprintf('Unable to find tabs in shownotes for episode %s.', $this->episode->getCode()));
        }

        return null;
    }

    private function parseTabs(array $nodes): array
    {
        $tabs = [];

        foreach ($this->parseOutlines($nodes) as $node) {
            $tabs[$node['text']] = $this->parseOutlines($node);
        }

        return $tabs;
    }

    private function getTab(...$names): array
    {
        if (!$this->tabs) {
            return [];
        }

        foreach ($names as $name) {
            if (isset($this->tabs[$name])) {
                return $this->tabs[$name];
            }
        }

        $this->logger->error(sprintf('Requested tab(s) %s not found in shownotes for episode %s.', implode(', ', $names), $this->episode->getCode()));

        return [];
    }

    private function parseOutlines(?array $node): array
    {
        $outlines = $node['@outlines'] ?? [];

        foreach ($outlines as $key => $outline) {
            if (isset($outline['text'])) {
                $outlines[$key]['text'] = trim($outline['text']);
            }
        }

        return $outlines;
    }

    private function parseClipOutline(array $node): ?array
    {
        $type = $node['type'] ?? (isset($node['url']) ? 'link' : 'text');
        $title = trim(strip_tags($node['text']));
        $uri = $node['url'] ?? false;

        if ($type === 'link' && preg_match('/^.*\.(mp3|mp4|m4a|3gp|ogg|wma|webm)$/i', $title)) {
            $type = 'audio';
        } elseif ($type === 'image' && $title == '') {
            $title = 'Image';
        } elseif ($type === 'text' && $title == '') {
            if (str_contains($node['text'], '<img')) {
                $matches = [];
                preg_match('/src="([^"]+)"/', $node['text'], $matches);

                if (isset($matches[1])) {
                    $type = 'image';
                    $title = 'Image';
                    $uri = $matches[1];
                }
            } else {
                return null;
            }
        }

        return [
            'type' => $type,
            'title' => $title,
            'sort_title' => strtolower($title),
            'uri' => $uri,
        ];
    }
}
