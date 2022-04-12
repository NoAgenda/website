<?php

namespace App\Crawling;

use App\Entity\Episode;
use App\Entity\ScheduledFileDownload;
use App\Exception\FileDownloadException;
use App\Message\Crawl;
use App\Repository\ScheduledFileDownloadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClientInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class FileDownloader
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScheduledFileDownloadRepository $scheduledFileDownloadRepository,
        private HttpMethodsClientInterface $httpClient,
        private MessageBusInterface $messenger,
        private array $staticSources = [],
    ) {
        $this->logger = new NullLogger();
    }

    public function download(string $uri, string $path, \DateTime $ifModifiedSince = null): \DateTime
    {
        $filesystem = new Filesystem();

        if ($staticPath = $this->getStaticPath($uri)) {
            $lastModifiedAt = (new \DateTime())->setTimestamp(filemtime($staticPath));

            if ($ifModifiedSince && ($lastModifiedAt->getTimestamp() - $ifModifiedSince->getTimestamp()) <= 0) {
                $lastModifiedAt = $ifModifiedSince;

                $this->logger->debug(sprintf('No changes to file "%s". Last modified at %s.', $uri, $lastModifiedAt->format('Y-m-d H:i:s')));
            } else {
                if ($ifModifiedSince) {
                    $this->logger->info(sprintf('File "%s" was changed. Last modified at %s.', $uri, $lastModifiedAt->format('Y-m-d H:i:s')));
                } else {
                    $this->logger->info(sprintf('File "%s" has been (re)downloaded. Last modified at %s.', $uri, $lastModifiedAt->format('Y-m-d H:i:s')));
                }

                $filesystem->copy($staticPath, $path);
            }
        } else {
            $headers = [];

            if (null !== $ifModifiedSince) {
                $headers['If-Modified-Since'] = $ifModifiedSince->format('D, M d Y H:i:s O+');
            }

            try {
                $response = $this->httpClient->get($uri, $headers);
            } catch (\Throwable $exception) {
                throw new FileDownloadException(sprintf('Failed to download "%s": %s', $uri, $exception->getMessage()), 0, $exception);
            }

            if (304 === $response->getStatusCode()) {
                $lastModifiedAt = $ifModifiedSince;

                $this->logger->debug(sprintf('No changes to file "%s". Last modified at %s.', $uri, $lastModifiedAt->format('Y-m-d H:i:s')));
            } else {
                $lastModifiedAt = new \DateTime($response->getHeaderLine('Last-Modified'));

                if ($ifModifiedSince) {
                    $this->logger->info(sprintf('File "%s" was changed. Last modified at %s.', $uri, $lastModifiedAt->format('Y-m-d H:i:s')));
                } else {
                    $this->logger->info(sprintf('File "%s" has been (re)downloaded. Last modified at %s.', $uri, $lastModifiedAt->format('Y-m-d H:i:s')));
                }

                $filesystem->dumpFile($path, $response->getBody()->getContents());
            }
        }

        return $lastModifiedAt;
    }

    public function updateSchedule(string $data, Episode $episode, \DateTime $lastModifiedAt, \DateTime $initializedAt): void
    {
        $scheduledFileDownload = $this->scheduledFileDownloadRepository->findDownload($data, $episode);

        if ($scheduledFileDownload && $initializedAt !== $scheduledFileDownload->getInitializedAt()) {
            $this->logger->debug('File download has already been rescheduled');
            return;
        }

        if (!$interval = $this->calculateCrawlingInterval($lastModifiedAt)) {
            if ($scheduledFileDownload) {
                $this->entityManager->remove($scheduledFileDownload);
            }

            $this->logger->debug('File download doesn\'t have to be rescheduled');
            return;
        }

        if (!$scheduledFileDownload) {
            $scheduledFileDownload = (new ScheduledFileDownload())
                ->setData($data)
                ->setEpisode($episode)
                ->setInitializedAt($initializedAt)
            ;
        }

        if ($lastModifiedAt !== $scheduledFileDownload->getLastModifiedAt()) {
            $scheduledFileDownload->setLastModifiedAt($lastModifiedAt);

            $this->entityManager->persist($scheduledFileDownload);
        }

        $this->logger->debug('Rescheduling file download');
        $message = new Crawl($data, $episode->getCode(), $lastModifiedAt, $initializedAt);
        $envelope = new Envelope($message, [
            DelayStamp::delayFor($interval),
        ]);

        $this->messenger->dispatch($envelope);
    }

    private function calculateCrawlingInterval(\DateTime $lastModifiedAt): ?\DateInterval
    {
        $diff = (new \DateTime())->diff($lastModifiedAt);

        if ($diff->y > 0 || $diff->m > 0 || $diff->d > 14) {
            return null;
        }

        if ($diff->d > 3) {
            return new \DateInterval('PT24H');
        }

        if ($diff->h > 8) {
            return new \DateInterval('PT3H');
        }

        return new \DateInterval('PT30M');
    }

    private function getStaticPath(string $uri): ?string
    {
        foreach ($this->staticSources as $baseUri => $basePath) {
            if (!str_starts_with($uri, $baseUri)) {
                continue;
            }

            $baseUri = rtrim($baseUri, '/') . '/';
            $basePath = rtrim($basePath, '/') . '/';

            $path = $basePath . substr($uri, strlen($baseUri));

            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
