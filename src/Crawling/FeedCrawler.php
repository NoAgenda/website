<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Entity\EpisodePart;
use App\Entity\User;
use App\Message\CrawlEpisodeFiles;
use App\Message\CrawlEpisodeShownotes;
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
use Symfony\Component\Messenger\MessageBusInterface;

class FeedCrawler
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
        $entries = $this->crawlFeed();

        $episodeRepository = $this->entityManager->getRepository(Episode::class);
        $episodes = $episodeRepository->findFeedEpisodes();

        foreach ($entries as $entry) {
            $this->handleEntry($entry, $episodes[$entry['code']] ?? null);
        }
    }

    private function crawlFeed(): array
    {
        $response = $this->httpClient->get('http://feed.nashownotes.com/rss.xml');
        $source = $response->getBody()->getContents();

        $entries = [];

        Reader::registerExtension('Podcast');

        /** @var RssFeed $rssFeed */
        $rssFeed = Reader::importString($source);
        /** @var PodcastFeed $podcastFeed */
        $podcastFeed = $rssFeed->getExtension('Podcast');

        // Parse feed attributes
        $podcastFeed->getXpath();

        /** @var RssEntry $rssItem */
        foreach ($rssFeed as $rssItem) {
            /** @var PodcastEntry $podcastItem */
            $podcastItem = $rssItem->getExtension('Podcast');

            preg_match('/^(\d+): "(.*)"$/', $rssItem->getTitle(), $matches);
            list(, $code, $name) = $matches;

            $xpath = $podcastItem->getXpath();
            $cover = $xpath->evaluate('string(' . $podcastItem->getXpathPrefix() . '/itunes:image/@href)');

            /** @var object $enclosure */
            $enclosure = $rssItem->getEnclosure();
            $recording = $enclosure->url;

            $entries[] = [
                'code' => $code,
                'name' => $name,
                'author' => $podcastItem->getCastAuthor(),
                'coverUri' => $cover,
                'recordingUri' => $recording,
                'publishedAt' => $rssItem->getDateCreated(),
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
                ->setCrawlerOutput($entry)
            ;

            $this->entityManager->persist($episode);

            $crawlFilesMessage = new CrawlEpisodeFiles($episode->getCode());
            $this->messenger->dispatch($crawlFilesMessage);

            $crawlShownotesMessage = new CrawlEpisodeShownotes($episode->getCode());
            $this->messenger->dispatch($crawlShownotesMessage);
        }

        if ($new) {
            $part = new EpisodePart();

            $part
                ->setEpisode($episode)
                ->setCreator($this->getDefaultUser())
                ->setName('Start of Show')
                ->setStartsAt(0)
            ;

            $this->entityManager->persist($part);

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
