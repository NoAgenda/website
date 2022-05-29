<?php

namespace App\Crawling;

use App\Entity\BatSignal;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BatSignalCrawler implements CrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BatSignalRepository $batSignalRepository,
        private HttpClientInterface $mastodonClient,
        private ?string $mastodonAccessToken,
        private int $mastodonAccountId,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(): void
    {
        if (!$this->mastodonAccessToken) {
            $this->logger->critical('Mastodon access token not found. Skipping crawling of bat signal.');

            return;
        }

        if (!$signal = $this->crawlBatSignal()) {
            $this->logger->debug('No bat signal found.');

            return;
        }

        if ($this->batSignalRepository->exists($signal)) {
            $this->logger->debug('Found bat signal already exists.');

            return;
        }

        $this->logger->info(sprintf(
            'Found new bat signal with code "%s" published at %s.',
            $signal->getCode(),
            $signal->getDeployedAt()->format('Y-m-d H:i:s'),
        ));

        $this->entityManager->persist($signal);
    }

    private function crawlBatSignal(): ?BatSignal
    {
        $response = $this->mastodonClient->request('GET', sprintf('accounts/%s/statuses', $this->mastodonAccountId));
        $responseCode = $response->getStatusCode();

        if ($responseCode >= 300) {
            $this->logger->warning(sprintf('Failed to crawl bat signal feed. HTTP response code: %s', $responseCode));

            return null;
        }

        $entries = json_decode($response->getContent(), true);

        foreach ($entries as $entry) {
            if (str_contains($entry['content'] ?? '', '#@pocketnoagenda')) {
                preg_match('/episode (\d+)/', $entry['content'],$matches);
                list(, $code) = $matches;

                return (new BatSignal())
                    ->setCode($code)
                    ->setDeployedAt(new \DateTime($entry['created_at'] ?? null))
                ;
            }
        }

        return null;
    }
}
