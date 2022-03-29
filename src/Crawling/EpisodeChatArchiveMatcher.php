<?php

namespace App\Crawling;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class EpisodeChatArchiveMatcher implements EpisodeCrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode): void
    {
        if (!$episode->getRecordedAt()) {
            $this->logger->warning(sprintf('Unable to match the chat archive for episode %s because the recording time is unknown.', $episode->getCode()));

            return;
        }

        $path = sprintf('%s/chat_logs/%s.log', $_SERVER['APP_STORAGE_PATH'], $episode->getRecordedAt()->format('Ymd'));

        if (!file_exists($path)) {
            $this->logger->warning(sprintf('No chat logs found for episode %s.', $episode->getCode()));

            return;
        }

        $logs = explode("\n", file_get_contents($path));
        $messages = $this->matchMessages($episode, $logs);

        if (!$messageCount = count($messages)) {
            $this->logger->warning(sprintf('No chat messages found for episode %s, even though chat logs are available.', $episode->getCode()));

            return;
        }

        $this->logger->info(sprintf('Matched %s chat messages for episode %s.', $messageCount, $episode->getCode()));

        $output = json_encode($messages);

        if (json_last_error()) {
            $this->logger->critical(sprintf('Failed to encode chat archive for episode %s: %s.', $episode->getCode(), json_last_error_msg()));

            return;
        }

        $path = sprintf('%s/chat_archives/%s.json', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

        if ($path !== $episode->getChatArchivePath()) {
            $episode->setChatArchivePath($path);

            $this->entityManager->persist($episode);
        }

        file_put_contents($path, $output);
    }

    private function matchMessages(Episode $episode, array $logs): array
    {
        $messages = [];

        foreach ($logs as $log) {
            if (!strlen(trim($log)) || !str_contains($log, '>>>')) {
                continue;
            }

            list($crawledAt, $rawMessage) = explode('>>>', $log);

            $crawledAt = new \DateTime(trim($crawledAt));

            $interval = $crawledAt->getTimestamp() - $episode->getRecordedAt()->getTimestamp();

            if ($interval <= 0 || $interval >= $episode->getDuration()) {
                continue;
            }

            if (strpos($rawMessage, 'PRIVMSG #NoAgenda') === false) {
                continue;
            }

            preg_match('/:([^!]+)!([^@]+)@(\S+) PRIVMSG #NoAgenda :(.+)/', $rawMessage, $matches);

            if (!isset($matches[0])) {
                continue;
            }

            list(, $username, $client, $ip, $contents) = $matches;

            $messages[] = [
                'username' => $username,
                'contents' => nl2br($contents),
                'timestamp' => $interval,
            ];
        }

        return $messages;
    }
}
