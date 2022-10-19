<?php

namespace App\Crawling;

use App\Entity\BatSignal;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Sentry\captureException;
use function Sentry\captureMessage;

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

        $this->boostBatSignal($signal);

        $this->entityManager->persist($signal);
    }

    private function boostBatSignal(BatSignal $signal): void
    {
        try {
            $response = $this->mastodonClient->request('POST', sprintf('status/%s/reblog', $signal->postId));

            if (200 !== $statusCode = $response->getStatusCode()) {
                $message = sprintf('Failed to boost bat signal on Mastodon: Response code %s', $statusCode);
                $this->logger->warning($message);

                captureMessage($message);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(sprintf('An exception occurred while boosting the bat signal on Mastodon: %s', $exception->getMessage()), ['exception' => $exception]);

            captureException($exception);
        }
    }

    private function crawlBatSignal(): ?BatSignal
    {
        $response = $this->mastodonClient->request('GET', sprintf('accounts/%s/statuses', $this->mastodonAccountId));
        $responseCode = $response->getStatusCode();

        if ($responseCode >= 300) {
            $message = sprintf('Failed to crawl bat signal on Mastodon: Response code %s', $responseCode);
            $this->logger->warning($message);

            captureMessage($message);

            return null;
        }

        $entries = json_decode($response->getContent(), true);

        foreach ($entries as $entry) {
            if (str_contains($entry['content'] ?? '', '#@pocketnoagenda')) {
                preg_match('/episode (\d+)/', $entry['content'],$matches);
                list(, $code) = $matches;

                $signal = (new BatSignal())
                    ->setCode($code)
                    ->setDeployedAt(new \DateTime($entry['created_at'] ?? null));

                $signal->postId = $entry['id'];

                return $signal;
            }
        }

        return null;
    }
}
