<?php

namespace App\Crawling;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClientInterface;
use Laminas\Feed\Reader\Entry\Rss as RssEntry;
use Laminas\Feed\Reader\Feed\Rss as RssFeed;
use Laminas\Feed\Reader\Extension\Podcast\Entry as PodcastEntry;
use Laminas\Feed\Reader\Extension\Podcast\Feed as PodcastFeed;
use Laminas\Feed\Reader\Reader;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\Cache\CacheInterface;

class FeedCrawler implements CrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpMethodsClientInterface $httpClient,
        private CacheInterface $cache,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(): void
    {
        if (null === $entries = $this->crawlFeed()) {
            return;
        }

        $earliestPublishDate = min(array_column($entries, 'publishedAt'));

        $episodeRepository = $this->entityManager->getRepository(Episode::class);
        $episodes = $episodeRepository->findEpisodesSince($earliestPublishDate);

        foreach ($entries as $entry) {
            $this->handleEntry($entry, $episodes[$entry['code']] ?? null);
        }
    }

    private function crawlFeed(): ?array
    {
        $lastModifiedCache = $this->cache->getItem('feed.last_modified');
        $lastModifiedAt = $lastModifiedCache->get();

        $headers = [];

        if (null !== $lastModifiedAt) {
            $headers['If-Modified-Since'] = $lastModifiedAt;
        }

        $response = $this->httpClient->get('http://feed.nashownotes.com/rss.xml', $headers);
        $responseCode = $response->getStatusCode();

        if (304 === $responseCode) {
            $this->logger->debug(sprintf('No changes to feed. Last modified at %s.', $lastModifiedAt));

            return null;
        } elseif ($responseCode >= 300) {
            $this->logger->warning(sprintf('Failed to crawl feed (status code: %s)', $responseCode));
        }

        $lastModifiedAt = $response->getHeaderLine('Last-Modified');

        $this->logger->debug(sprintf('Feed has been changed. Modified at %s.', $lastModifiedAt));

        $lastModifiedCache->set($lastModifiedAt);
        $this->cache->save($lastModifiedCache);

        $source = $response->getBody()->getContents();

        $entries = [];

        Reader::registerExtension('Podcast');

        /** @var RssFeed|PodcastFeed $feed */
        $feed = Reader::importString($source);

        // Parse feed attributes
        $feed->getXpath();

        /** @var RssEntry|PodcastEntry $feedItem */
        foreach ($feed as $feedItem) {
            preg_match('/^(\d+): "(.*)"$/', $feedItem->getTitle(), $matches);
            $titleParts = explode(':', $feedItem->getTitle(), 2);

            if (count($titleParts) < 2) {
                $this->logger->alert(sprintf('Failed to parse episode title: %s', $feedItem->getTitle()));

                $titleParts[1] = $titleParts[0];
            }

            list($code, $name) = $titleParts;

            $xpath = $feedItem->getXpath();

            $entries[] = [
                'code' => trim($code),
                'name' => trim($name, ' "'),
                'author' => $feedItem->getCastAuthor(),
                'coverUri' => $feedItem->getItunesImage(),
                'recordingUri' => $feedItem->getEnclosure()->url,
                'publishedAt' => $feedItem->getDateCreated(),
                'transcriptUri' => $xpath->evaluate('string(' . $feedItem->getXpathPrefix() . '/podcast:transcript/@url)'), // todo podcasting 2.0 support in library
                'transcriptType' => 'srt',
            ];
        }

        return array_reverse($entries);
    }

    private function handleEntry(array $entry, ?Episode $episode): void
    {
        if (!$episode) {
            $this->logger->info(sprintf('New episode: %s', $entry['code']));

            $episode = new Episode();
        } else if (json_encode($episode->getCrawlerOutput()) !== json_encode($entry)) {
            $this->logger->info(sprintf('Episode updated: %s', $episode->getCode()));
        } else {
            return;
        }

        $episode
            ->setCode($entry['code'])
            ->setName($entry['name'])
            ->setAuthor($entry['author'])
            ->setPublishedAt($entry['publishedAt'])
            ->setCoverUri($entry['coverUri'])
            ->setRecordingUri($entry['recordingUri'])
            ->setTranscriptUri($entry['transcriptUri'])
            ->setTranscriptType('srt')
            ->setCrawlerOutput($entry)
        ;

        $this->entityManager->persist($episode);
    }
}
