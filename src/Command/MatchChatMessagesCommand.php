<?php

namespace App\Command;

use App\Entity\ChatMessage;
use App\Entity\Episode;
use App\Repository\BatSignalRepository;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use ForceUTF8\Encoding;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MatchChatMessagesCommand extends Command
{
    protected static $defaultName = 'app:match-chat-messages';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var BatSignalRepository
     */
    private $batSignalRepository;

    /**
     * @var EpisodeRepository
     */
    private $episodeRepository;

    /**
     * @var string
     */
    private $storagePath;

    public function __construct(
        ?string $name = null,
        EntityManagerInterface $entityManager,
        BatSignalRepository $batSignalRepository,
        EpisodeRepository $episodeRepository,
        string $storagePath
    )
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->batSignalRepository = $batSignalRepository;
        $this->episodeRepository = $episodeRepository;
        $this->storagePath = $storagePath;
    }

    protected function configure()
    {
        $this
            ->setDescription('Matches chat messages from the troll room to the recording time')
            ->addArgument('episode', InputArgument::REQUIRED, 'The episode code')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save crawling results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $code = $input->getArgument('episode');
        $episode = $this->episodeRepository->findOneBy(['code' => $code]);

        if ($episode === null) {
            $io->error(sprintf('Unknown episode "%s".', $code));

            return 1;
        }

        if (!$episode->getRecordedAt()) {
            $io->error('Unable to match chat messages for an episode without a recording time.');

            return 1;
        }

        $messages = $this->matchMessages($input, $output, $episode);

        if (count($messages) == 0) {
            $io->note('No messages were matched to the episode recording time.');

            return 0;
        }

        $episode->setChatMessages(true);
        $this->entityManager->persist($episode);

        if ($save) {
            $this->saveMessages($input, $output, $episode, $messages);

            $this->entityManager->flush();

            $io->success(sprintf('Saved %s messages for episode %s.', count($messages), $episode->getCode()));
        }
        else {
            $io->text(sprintf('Found %s messages.', count($messages)));

            $io->note('The crawling results have not been saved. Pass the `--save` option to save the results in the database.');
        }

        return 0;
    }

    protected function matchMessages(InputInterface $input, OutputInterface $output, Episode $episode)
    {
        $io = new SymfonyStyle($input, $output);

        // todo if recording time is close to the next day, also initially include those logs

        $logPath = sprintf('%s/chat_logs/%s.log', $this->storagePath, $episode->getRecordedAt()->format('Ymd'));
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

            list(, $username, $client, $ip, $message) = $matches;

            $messages[] = [
                'username' => htmlentities($username),
                'contents' => htmlentities($message),
                'postedAt' => $interval,
            ];
        }

        return $messages;
    }

    protected function saveMessages(InputInterface $input, OutputInterface $output, Episode $episode, array $messages)
    {
        foreach ($messages as $messageDefinition) {
            $message = (new ChatMessage)
                ->setEpisode($episode)
                ->setUsername($messageDefinition['username'])
                ->setContents(Encoding::fixUTF8($messageDefinition['contents']))
                ->setPostedAt($messageDefinition['postedAt'])
                ->fromTrollRoom()
            ;

            $this->entityManager->persist($message);
        }
    }
}
