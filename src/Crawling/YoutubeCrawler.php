<?php

namespace App\Crawling;

use App\Entity\Video;
use App\Repository\VideoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YoutubeCrawler implements CrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private VideoRepository $videoRepository,
        private HttpClientInterface $httpClient,
        private ?string $youtubeKey = null,
        private ?string $youtubePlaylistId = null,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(): void
    {
        if (!$this->youtubeKey) {
            $this->logger->critical('YouTube API key not found. Skipping crawling of ANA videos.');

            return;
        }

        foreach ($this->fetchPlaylist() as $youtubeId) {
            $video = $this->videoRepository->findOneBy(['youtubeId' => $youtubeId]) ?: new Video($youtubeId);

            $this->fetchVideo($video);
        }
    }

    private function fetchPlaylist(): \Generator
    {
        $uri = sprintf('https://www.googleapis.com/youtube/v3/playlistItems?%s', http_build_query([
            'key' => $this->youtubeKey,
            'playlistId' => $this->youtubePlaylistId,
            'part' => 'contentDetails',
            'maxResults' => 10,
        ]));

        $response = $this->httpClient->request('GET', $uri);
        $contents = json_decode($response->getContent(), true);

        foreach ($contents['items'] as $item) {
            yield $item['contentDetails']['videoId'];
        }
    }

    private function fetchVideo(Video $video): void
    {
        $uri = sprintf('https://www.googleapis.com/youtube/v3/videos?%s', http_build_query([
            'key' => $this->youtubeKey,
            'id' => $video->getYoutubeId(),
            'part' => 'snippet',
        ]));

        $headers = [];

        if ($etag = $video->getYoutubeEtag()) {
            $headers['If-None-Match'] = $etag;
        }

        $response = $this->httpClient->request('GET', $uri, [
            'headers' => $headers,
        ]);

        if (304 === $response->getStatusCode()) {
            return;
        }

        $contents = json_decode($response->getContent(), true);
        $data = $contents['items'][0];

        $video
            ->setTitle($data['snippet']['title'])
            ->setPublishedAt(new \DateTime($data['snippet']['publishedAt']))
            ->setYoutubeEtag($contents['etag'])
        ;

        if (!$this->entityManager->contains($video)) {
            $this->logger->info(sprintf('Found new Animated NA video: %s', $video->getTitle()));
        } else {
            $this->logger->info(sprintf('Updated Animated NA video: %s', $video->getTitle()));
        }

        $this->entityManager->persist($video);
    }
}
