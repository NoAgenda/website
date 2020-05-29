<?php

namespace App\Crawling;

use App\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClient;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class YoutubeCrawler
{
    use LoggerAwareTrait;

    private $httpClient;
    private $entityManager;

    public function __construct(HttpMethodsClient $httpClient, EntityManagerInterface $entityManager)
    {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->logger = new NullLogger();
    }

    public function crawl(): void
    {
        if (!$_SERVER['YOUTUBE_KEY']) {
            return;
        }

        $videoRepository = $this->entityManager->getRepository(Video::class);

        $videoIds = $this->fetchAnimatedNaVideos();

        foreach ($videoIds as $videoId) {
            $video = $videoRepository->findOneBy(['youtubeId' => $videoId]);
            $video = $video ?: new Video();

            $etag = $video->getYoutubeEtag();

            $videoData = $this->fetchVideo($videoId, $etag);

            if (!$videoData) {
                continue;
            }

            $video->setTitle($videoData['snippet']['title']);
            $video->setPublishedAt(new \DateTime($videoData['snippet']['publishedAt']));
            $video->setYoutubeId($videoId);
            $video->setYoutubeEtag($etag);

            if (!$video->getId()) {
                $this->logger->info(sprintf('Found new Animated NA video: %s', $video->getTitle()));
            } else {
                $this->logger->info(sprintf('Updated Animated NA video: %s', $video->getTitle()));
            }

            $this->entityManager->persist($video);
        }
    }

    private function fetchAnimatedNaVideos(): array
    {
        $uri = sprintf('https://www.googleapis.com/youtube/v3/playlistItems?%s', http_build_query([
            'key' => $_SERVER['YOUTUBE_KEY'],
            'playlistId' => $_SERVER['YOUTUBE_ANIMATED_NA_UPLOADS_PLAYLIST_ID'],
            'part' => 'contentDetails',
            'maxResults' => 10,
        ]));

        $response = $this->httpClient->get($uri);

        $contents = json_decode($response->getBody()->getContents(), true);

        $items = [];
        foreach ($contents['items'] as $item) {
            $items[] = $item['contentDetails']['videoId'];
        }

        return $items;
    }

    private function fetchVideo(string $id, string &$etag = null): ?array
    {
        $uri = sprintf('https://www.googleapis.com/youtube/v3/videos?%s', http_build_query([
            'key' => $_SERVER['YOUTUBE_KEY'],
            'id' => $id,
            'part' => 'snippet',
        ]));

        $headers = [];

        if ($etag) {
            $headers['If-None-Match'] = $etag;
        }

        $response = $this->httpClient->get($uri, $headers);

        if (304 === $response->getStatusCode()) {
            return null;
        }

        $contents = json_decode($response->getBody()->getContents(), true);

        $etag = $contents['etag'];

        return $contents['items'][0];
    }
}
