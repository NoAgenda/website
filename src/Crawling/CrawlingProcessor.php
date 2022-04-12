<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Message\Crawl;
use App\Repository\EpisodeRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\MessageBusInterface;
use function Sentry\captureException;

class CrawlingProcessor
{
    use LoggerAwareTrait;

    public static array $crawlerClasses = [
        'bat_signal' => BatSignalCrawler::class,
        'chat_archive' => EpisodeChatArchiveMatcher::class,
        'cover' => EpisodeCoverCrawler::class,
        'duration' => EpisodeDurationCrawler::class,
        'feed' => FeedCrawler::class,
        'recording_time' => EpisodeRecordingTimeMatcher::class,
        'shownotes' => EpisodeShownotesCrawler::class,
        'transcript' => EpisodeTranscriptCrawler::class,
        'youtube' => YoutubeCrawler::class,
    ];

    public function __construct(
        private EpisodeRepository $episodeRepository,
        private MessageBusInterface $messenger,
        private FileDownloader $fileDownloader,
        private ContainerInterface $crawlers,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(string $data, Episode $episode = null, \DateTimeInterface $lastModifiedAt = null, \DateTimeInterface $initializedAt = null): void
    {
        $crawlerName = $this->getCrawlerName($data, $episode);
        $crawler = $this->crawlers->get($crawlerName);

        try {
            if (is_subclass_of($crawlerName, EpisodeFileCrawlerInterface::class)) {
                if ($lastModifiedAt = $crawler->crawl($episode, $lastModifiedAt)) {
                    $this->fileDownloader->updateSchedule($data, $episode, $lastModifiedAt, $initializedAt ?? new \DateTime());
                }
            } elseif (is_subclass_of($crawlerName, EpisodeCrawlerInterface::class)) {
                $crawler->crawl($episode);
            } else {
                $crawler->crawl();
            }
        } catch (\Throwable $exception) {
            $this->logger->error(sprintf('An error occurred: %s', $exception->getMessage()));

            captureException($exception);
        }
    }

    public function enqueue(string $data, Episode $episode = null): void
    {
        $crawlerName = $this->getCrawlerName($data, $episode);
        $code = $episode?->getCode();

        $this->messenger->dispatch(new Crawl($data, $code));
    }

    private function getCrawlerName(string $data, ?Episode $episode): string
    {
        if (!$crawlerName = self::$crawlerClasses[$data] ?? false) {
            throw new \InvalidArgumentException(sprintf('Invalid data type: %s', $data));
        }

        if (is_subclass_of($crawlerName, CrawlerInterface::class) && !$episode) {
            throw new \InvalidArgumentException(sprintf('Crawling of %s requires an episode.', $data));
        }

        return $crawlerName;
    }
}
