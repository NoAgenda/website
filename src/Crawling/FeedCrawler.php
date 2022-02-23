<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Entity\User;
use App\Message\CrawlEpisodeFiles;
use App\Message\CrawlEpisodeShownotes;
use App\Message\CrawlEpisodeTranscript;
use App\Message\EpisodeNotification;
use App\Message\MatchEpisodeRecordingTime;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClient;
use Laminas\Feed\Reader\Entry\Rss as RssEntry;
use Laminas\Feed\Reader\Feed\Rss as RssFeed;
use Laminas\Feed\Reader\Extension\Podcast\Entry as PodcastEntry;
use Laminas\Feed\Reader\Extension\Podcast\Feed as PodcastFeed;
use Laminas\Feed\Reader\Reader;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Contracts\Cache\CacheInterface;

class FeedCrawler
{
    use LoggerAwareTrait;

    public function __construct(
        private HttpMethodsClient $httpClient,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messenger,
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

        if (304 === $response->getStatusCode()) {
            $this->logger->debug(sprintf('No changes to feed. Last modified at %s', $lastModifiedAt));

            return null;
        }

        $lastModifiedAt = $response->getHeaderLine('Last-Modified');

        $this->logger->debug(sprintf('Feed has been changed. Modified at %s', $lastModifiedAt));

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
            list(, $code, $name) = $matches;

            $xpath = $feedItem->getXpath();

            $entries[] = [
                'code' => $code,
                'name' => $name,
                'author' => $feedItem->getCastAuthor(),
                'coverUri' => $feedItem->getItunesImage(),
                'recordingUri' => $feedItem->getEnclosure()->url,
                'publishedAt' => $feedItem->getDateCreated(),
                'transcriptUri' => $xpath->evaluate('string(' . $feedItem->getXpathPrefix() . '/podcast:transcript/@url)'),
                'transcriptType' => 'srt',
            ];
        }

        return array_reverse($entries);
    }

    private function handleEntry(array $entry, ?Episode $episode): void
    {
        $new = false;
        $updated = false;

        if (null === $episode) {
            $new = true;

            $this->logger->info(sprintf('New episode: %s', $entry['code']));

            $episode = new Episode();
        } elseif ($episode->getCrawlerOutput() != $entry) {
            $updated = true;

            $this->logger->info(sprintf('Episode updated: %s', $episode->getCode()));
        }

        if ($new || $updated) {
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

            $crawlFilesMessage = new CrawlEpisodeFiles($episode->getCode());
            $this->messenger->dispatch($crawlFilesMessage);

            $crawlShownotesMessage = new CrawlEpisodeShownotes($episode->getCode());
            $this->messenger->dispatch($crawlShownotesMessage);

            if ($episode->getTranscriptUri()) {
                $crawlTranscriptMessage = new CrawlEpisodeTranscript($episode->getCode());
                if ($new) {
                    $crawlTranscriptMessage = new Envelope($crawlTranscriptMessage, [
                        new DelayStamp(1000 * 60 * 60 * 32), // Delay 32 hours
                    ]);
                }
                $this->messenger->dispatch($crawlTranscriptMessage);
            }
        }

        if ($new) {
            $chapter = new EpisodeChapter();

            $chapter
                ->setEpisode($episode)
                ->setCreator($this->getDefaultUser())
                ->setName('Start of Show')
                ->setStartsAt(0)
            ;

            $this->entityManager->persist($chapter);

            $notificationMessage = new EpisodeNotification($episode->getCode());
            $this->messenger->dispatch($notificationMessage);

            $matchRecordingTimeMessage = new MatchEpisodeRecordingTime($episode->getCode());
            $this->messenger->dispatch($matchRecordingTimeMessage);
        }
    }

    private function getDefaultUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'Woodstock']);
    }
}
