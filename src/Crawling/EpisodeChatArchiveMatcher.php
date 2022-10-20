<?php

namespace App\Crawling;

use App\Entity\BatSignal;
use App\Entity\Episode;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class EpisodeChatArchiveMatcher implements EpisodeCrawlerInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BatSignalRepository $batSignalRepository,
    ) {
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode): void
    {
        if (!$episode->getDuration()) {
            $this->logger->warning(sprintf('Unable to match the chat archive for episode %s because its duration is unknown.', $episode->getCode()));

            return;
        }

        if (!$signal = $this->batSignalRepository->findOneByEpisode($episode)) {
            $this->logger->warning(sprintf('Unable to find a bat signal matching episode %s.', $episode->getCode()));

            return;
        }

        if (!$chatLogs = $this->getChatLogs($episode, $signal)) {
            $this->logger->warning(sprintf('No chat logs found for episode %s.', $episode->getCode()));
        }

        if (!$episode->getRecordedAt()) {
            $this->logger->warning(sprintf('Unable to match the chat archive for episode %s because the recording time is unknown.', $episode->getCode()));

            return;
        }

        $messages = $this->matchMessages($episode, $chatLogs);

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

    private function findChatLogs(Episode $episode, BatSignal $signal): bool
    {
        $episodeChatLogsPath = sprintf('%s/episode_chat_logs/%s', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());
        $chatLogsPath = sprintf('%s/chat_logs', $_SERVER['APP_STORAGE_PATH']);

        if (file_exists($episodeChatLogsPath)) {
            return true;
        }

        $recordingDates = $this->getRecordingDates($episode, $signal);

        $files = (new Finder())
            ->files()
            ->in($chatLogsPath)
            ->filter(fn (\SplFileInfo $file) => in_array($file->getFilenameWithoutExtension(), $recordingDates));

        if (!count($files)) {
            return false;
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($episodeChatLogsPath);

        foreach ($files as $file) {
            $filesystem->copy($file->getPathname(), $episodeChatLogsPath . '/' . $file->getFilename());
        }

        return true;
    }

    private function getChatLogs(Episode $episode, BatSignal $signal): ?array
    {
        $episodeChatLogsPath = sprintf('%s/episode_chat_logs/%s', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

        if (!$this->findChatLogs($episode, $signal)) {
            return null;
        }

        $recordingDates = $this->getRecordingDates($episode, $signal);

        $files = (new Finder())
            ->files()
            ->in($episodeChatLogsPath)
            ->filter(fn (\SplFileInfo $file) => in_array($file->getFilenameWithoutExtension(), $recordingDates))
            ->sortByName();

        $chatLogs = [];

        foreach ($files as $file) {
            $contents = file_get_contents($file->getPathname());

            $chatLogs = array_merge($chatLogs, explode("\n", $contents));
        }

        return $chatLogs;
    }

    private function getRecordingDates(Episode $episode, BatSignal $signal): array
    {
        // Approximate recording time
        $recordingPadding = $episode->getDuration();
        $recordingPadding += 60 * 60; // 60 minutes

        $recordedAfter = $signal->getDeployedAt();
        $recordedBefore = (new \DateTime('@'.$recordedAfter->getTimestamp()))
            ->add(new \DateInterval(sprintf('PT%dS', $recordingPadding)));

        $recordingPeriod = new \DatePeriod(
            $recordedAfter,
            new \DateInterval('PT1H'),
            $recordedBefore,
        );

        return array_unique(array_map(
            fn (\DateTime $interval) => $interval->format('Ymd'),
            iterator_to_array($recordingPeriod),
        ));
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

            if (!$message = ChatRecorder::parseMessage($rawMessage)) {
                continue;
            }

            $message['timestamp'] = $interval;
            $messages[] = $message;
        }

        return $messages;
    }
}
