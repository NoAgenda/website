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
use function Symfony\Component\String\u;

class FileDownloader
{
    use LoggerAwareTrait;

    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private array $staticSources = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScheduledFileDownloadRepository $scheduledFileDownloadRepository,
        private HttpMethodsClientInterface $httpClient,
        private MessageBusInterface $messenger,
    ) {
        $this->logger = new NullLogger();

        if ($staticSources = $_SERVER['STATIC_SOURCES'] ?? false) {
            foreach (explode(',', $staticSources) as $staticSource) {
                $staticSource = explode('>', $staticSource);
                $this->staticSources[$staticSource[0]] = $staticSource[1];
            }
        }
    }

    public function download(string $uri, string $path, \DateTime $ifModifiedSince = null): \DateTime
    {
        $filesystem = new Filesystem();

        if ($staticPath = $this->getStaticPath($uri)) {
            $lastModifiedAt = (new \DateTime())->setTimestamp(filemtime($staticPath));
            $at = $lastModifiedAt->format(self::DATE_FORMAT);

            if ($ifModifiedSince && ($lastModifiedAt->getTimestamp() - $ifModifiedSince->getTimestamp()) <= 0) {
                $this->logger->debug(sprintf('No changes to file "%s". Last modified at %s.', $uri, $at));
            } else {
                if ($ifModifiedSince) {
                    $this->logger->info(sprintf('File "%s" was changed. Last modified at %s.', $uri, $at));
                } else {
                    $this->logger->info(sprintf('File "%s" has been (re)downloaded. Last modified at %s.', $uri, $at));
                }

                $filesystem->copy($staticPath, $path);
            }
        } else {
            $headers = [];

            if (null !== $ifModifiedSince) {
                $headers['If-Modified-Since'] = $ifModifiedSince->format(\DateTimeInterface::RFC1123);
            }

            try {
                $response = $this->httpClient->get($uri, $headers);
            } catch (\Throwable $exception) {
                throw new FileDownloadException(sprintf('Failed to download "%s": %s', $uri, $exception->getMessage()), 0, $exception);
            }

            if (304 === $response->getStatusCode()) {
                $lastModifiedAt = $ifModifiedSince;
                $at = $lastModifiedAt->format(self::DATE_FORMAT);

                $this->logger->debug(sprintf('No changes to file "%s". Last modified at %s.', $uri, $at));
            } else {
                $lastModifiedAt = new \DateTime($response->getHeaderLine('Last-Modified'));
                $at = $lastModifiedAt->format(self::DATE_FORMAT);

                if ($ifModifiedSince) {
                    $this->logger->info(sprintf('File "%s" was changed. Last modified at %s.', $uri, $at));
                } else {
                    $this->logger->info(sprintf('File "%s" has been (re)downloaded. Last modified at %s.', $uri, $at));
                }

                $filesystem->dumpFile($path, $response->getBody()->getContents());
            }
        }

        return $lastModifiedAt;
    }

    public function updateSchedule(string $data, Episode $episode, \DateTime $lastModifiedAt, \DateTime $initializedAt): void
    {
        $scheduledFileDownload = $this->scheduledFileDownloadRepository->findDownload($data, $episode);

        if ($scheduledFileDownload && $initializedAt->getTimestamp() !== $scheduledFileDownload->getInitializedAt()->getTimestamp()) {
            $this->logger->debug('File download has already been rescheduled');
            return;
        }

        if (!$interval = $this->calculateCrawlingInterval($lastModifiedAt)) {
            if ($scheduledFileDownload) {
                $this->entityManager->remove($scheduledFileDownload);
            }

            $this->logger->debug('End of file download cycle has been reached');
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

        $this->logger->debug(u('Rescheduled file download in ')->append($interval->format($interval->h > 0 ? '%h hours' : '%i minutes')));
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

    private function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
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
