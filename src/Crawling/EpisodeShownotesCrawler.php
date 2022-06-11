<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Exception\FileDownloadException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EpisodeShownotesCrawler implements EpisodeFileCrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private FileDownloader $fileDownloader,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode, \DateTime $ifModifiedSince = null): ?\DateTime
    {
        $publicResponse = $this->httpClient->request('GET', sprintf('http://%s.noagendanotes.com', $episode->getCode()));

        $publicContents = $publicResponse->getContent(); // Execute request
        $publicUri = $publicResponse->getInfo('url');

        if (null !== $publicUri && $publicUri !== $episode->getPublicShownotesUri()) {
            libxml_use_internal_errors(true);

            $publicDom = (new \DOMDocument())
                ->loadHTML($publicContents);
            $linkElement = (new \DOMXPath($publicDom))
                ->query('.//link[@title="OPML"]')
                ->item(0);

            if ($linkElement && $uri = $linkElement->getAttribute('href')) {
                $episode->setPublicShownotesUri($publicUri);
                $episode->setShownotesUri($uri);

                $this->entityManager->persist($episode);
            }
        }

        if (!$episode->getShownotesUri()) {
            throw new FileDownloadException(sprintf('Shownotes URI for episode %s could not be found.', $episode->getCode()));
        }

        $path = sprintf('%s/shownotes/%s.xml', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        $lastModifiedAt = $this->fileDownloader->download($episode->getShownotesUri(), $path, $ifModifiedSince);

        if ($path !== $episode->getShownotesPath()) {
            $episode->setShownotesPath($path);

            $this->entityManager->persist($episode);
        }

        return $lastModifiedAt;
    }
}
