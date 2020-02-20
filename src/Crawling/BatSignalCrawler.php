<?php

namespace App\Crawling;

use App\Entity\BatSignal;
use Colorfield\Mastodon\MastodonAPI;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class BatSignalCrawler
{
    use LoggerAwareTrait;

    private $api;
    private $entityManager;

    public function __construct(?MastodonAPI $api, EntityManagerInterface $entityManager)
    {
        $this->api = $api;
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
        if (!$this->api) {
            $this->logger->critical('Failed to initialize Mastodon API to fetch bat signal.');

            return null;
        }

        $entries = $this->api->get('/accounts/1/statuses');

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
