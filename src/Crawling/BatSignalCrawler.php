<?php

namespace App\Crawling;

use App\Entity\BatSignal;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClient;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class BatSignalCrawler
{
    use LoggerAwareTrait;

    private $httpClient;
    private $entityManager;

    public function __construct(HttpMethodsClient $mastodonClient, EntityManagerInterface $entityManager)
    {
        $this->httpClient = $mastodonClient;
        $this->entityManager = $entityManager;
        $this->logger = new NullLogger();
    }

    public function crawl(): void
    {
        $signalRepository = $this->entityManager->getRepository(BatSignal::class);

        $newSignal = $this->crawlBatSignal();

        if (!$newSignal) {
            return;
        }

        $existingSignal = $signalRepository->findOneBy([
            'code' => $newSignal->getCode(),
            'deployedAt' => $newSignal->getDeployedAt(),
        ]);

        if ($existingSignal) {
            $this->logger->debug('No new bat signal found.');

            return;
        }

        $this->logger->info(sprintf('Found new bat signal with code: %s', $newSignal->getCode()));

        $this->entityManager->persist($newSignal);
    }

    private function crawlBatSignal(): ?BatSignal
    {
        if (!$_SERVER['MASTODON_ACCESS_TOKEN']) {
            $this->logger->critical('Failed to initialize Mastodon API to fetch bat signal.');

            return null;
        }

        $response = $this->httpClient->get('/accounts/1/statuses');

        if ($response->getStatusCode() > 200) {
            $this->logger->critical('Failed to fetch messages from No Agenda Social.');

            return null;
        }

        $entries = json_decode($response->getBody()->getContents(), true);

        $post = false;

        foreach ($entries as $entry) {
            if (strpos($entry['content'], '#@pocketnoagenda') === false) {
                continue;
            }

            $post = $entry;

            break;
        }

        if (!$post) {
            return null;
        }

        preg_match('/episode (\d+)/', $post['content'],$matches);
        list(, $code) = $matches;

        $signal = new BatSignal();

        $signal->setCode($code);
        $signal->setDeployedAt(new \DateTime($post['created_at']));

        return $signal;
    }
}
