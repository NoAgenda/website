<?php

namespace App\Crawling;

use App\Entity\ChatMessage;
use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class EpisodeChatMessagesMatcher
{
    use LoggerAwareTrait;

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->logger = new NullLogger();
    }

    public function match(Episode $episode): void
    {
        if (!$episode->getRecordedAt()) {
            throw new \Exception('Unable to match chat messages for an episode without a recording time.');
        }

        $messages = $this->matchMessages($episode);

        if (0 === count($messages)) {
            $this->logger->info(sprintf('No chat messages found for episode %s.', $episode->getCode()));

            return;
        }

        $chatMessageRepository = $this->entityManager->getRepository(ChatMessage::class);
        $oldMessages = $chatMessageRepository->findByEpisode($episode);

        foreach ($oldMessages as $oldMessage) {
            $this->entityManager->remove($oldMessage);
        }

        foreach ($messages as $messageDefinition) {
            $message = new ChatMessage();

            $message
                ->setEpisode($episode)
                ->setUsername($messageDefinition['username'])
                ->setContents($messageDefinition['contents'])
                ->setPostedAt($messageDefinition['postedAt'])
                ->fromTrollRoom()
            ;

            $this->entityManager->persist($message);
        }

        $episode->setChatMessages(true);

        $this->entityManager->persist($episode);

        $this->logger->info(sprintf('Matched %s chat message for episode %s.', count($messages), $episode->getCode()));
    }

    protected function matchMessages(Episode $episode): array
    {
        // todo if recording time is close to the next day, also initially include those logs

        $logPath = sprintf('%s/chat_logs/%s.log', $_SERVER['APP_STORAGE_PATH'], $episode->getRecordedAt()->format('Ymd'));
        $rawLogs = explode("\n", file_get_contents($logPath));

        $messages = [];

        foreach ($rawLogs as $rawLog) {
            if (trim($rawLog) == '' || false === strpos($rawLog, '>>>')) {
                continue;
            }

            list($crawledAt, $rawMessage) = explode('>>>', $rawLog);

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
                'contents' => $contents,
                'postedAt' => $interval,
            ];
        }

        return $messages;
    }
}
