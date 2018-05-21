<?php

namespace App;

use Zend\Feed\Reader\Entry\Rss as RssEntry;
use Zend\Feed\Reader\Feed\Rss as RssFeed;
use Zend\Feed\Reader\Extension\Podcast\Entry as PodcastEntry;
use Zend\Feed\Reader\Extension\Podcast\Feed as PodcastFeed;
use Zend\Feed\Reader\Reader;

class FeedParser
{
    public function parse()
    {
        $source = file_get_contents('http://feed.nashownotes.com/rss.xml');
        $output = [
            'entries' => [],
        ];

        Reader::registerExtension('Podcast');

        /** @var RssFeed $feed */
        $feed = Reader::importString($source);
        /** @var PodcastFeed $podcast */
        $podcast = $feed->getExtension('Podcast');

        // parse feed attributes...
        $podcast->getXpath();

        /** @var RssEntry $item */
        foreach ($feed as $item) {
            /** @var PodcastEntry $podcast */
            $podcast = $item->getExtension('Podcast');

            preg_match('/^(\d+): "(.*)"$/', $item->getTitle(), $matches);
            list(, $code, $name) = $matches;

            $xpath = $podcast->getXpath();
            $image = $xpath->evaluate('string(' . $podcast->getXpathPrefix() . '/itunes:image/@href)');

            $output['entries'][] = [
                'code' => $code,
                'name' => $name,
                'author' => $podcast->getCastAuthor(),
                'image' => $image,
                'publishedAt' => $item->getDateCreated(),
                'enclosure' => $item->getEnclosure(),
            ];
        }

        return $output;
    }
}
