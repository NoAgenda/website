<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Exception\FileDownloadException;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClientInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class EpisodeShownotesCrawler implements EpisodeFileCrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpMethodsClientInterface $shownotesClient,
        private FileDownloader $fileDownloader,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode, \DateTime $ifModifiedSince = null): \DateTime
    {
        $frontResponse = $this->shownotesClient->get(sprintf('http://%s.noagendanotes.com', $episode->getCode()));
        $publicUri = $frontResponse->getHeaderLine('Location');

        if ($publicUri !== $episode->getPublicShownotesUri()) {
            $publicResponse = $this->shownotesClient->get($publicUri);
            $publicContents = $publicResponse->getBody()->getContents();

            libxml_use_internal_errors(true);

            $publicDom = new \DOMDocument();
            $publicDom->loadHTML($publicContents);

            $uri = (new \DOMXPath($publicDom))
                ->query('.//link[@title="OPML"]')
                ->item(0)
                ->getAttribute('href')
            ;

            $episode->setPublicShownotesUri($publicUri);
            $episode->setShownotesUri($uri);

            $this->entityManager->persist($episode);
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
