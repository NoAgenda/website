<?php

namespace App\Command;

use App\Entity\ChatSourceMessage;
use App\Repository\ChatSourceMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlChatCommand extends Command
{
    protected static $defaultName = 'app:crawl-chat';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ChatSourceMessageRepository
     */
    private $messageRepository;

    /**
     * @var boolean
     */
    private $joined;

    /**
     * @var integer
     */
    private $increment;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, ChatSourceMessageRepository $messageRepository)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawl messages from the troll room')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->joined = false;
        $this->increment = 0;

        $config = [
            'server' => 'irc.zeronode.net',
            'port' => 6667,
            'nick' => 'BotOfCourage',
        ];

        $server = [];
        $server['socket'] = fsockopen($config['server'], $config['port'], $errno, $errstr, 2);

        if ($server['socket']) {
            $this->sendCommand($output, $server, "PASS NOPASS\n\r");
            $this->sendCommand($output, $server, "NICK " . $config['nick'] . "\n\r");
            $this->sendCommand($output, $server, "USER " . $config['nick'] . " USING PHP IRC\n\r");

            while (!feof($server['socket'])) {
                $this->readServer($output, $server);
                flush();
                usleep(200);
            }
        }
    }

    protected function readServer(OutputInterface $output, array &$server)
    {
        $server['buffer'] = fgets($server['socket'], 1024);
        $output->writeln(sprintf('[RECEIVE] %s', trim($server['buffer'])));

        // Ping Pong
        if (substr($server['buffer'], 0, 4) == 'PING') {
            $this->sendCommand($output, $server, "PONG :" . substr($server['buffer'], 6) . "\n\r");

            return;
        }

        // Save messages
        if ($this->joined) {
            $message = (new ChatSourceMessage)
                ->setText($server['buffer'])
                ->setReceivedAt(new \DateTimeImmutable)
            ;

            $this->entityManager->persist($message);
            ++$this->increment;

            if ($this->increment >= 10) {
                $this->increment = 0;

                $this->entityManager->flush();

                $output->writeln('<bg=green;fg=white>Flushed database operations</bg=green;fg=white>');
            }
        }

        // Join channel
        if (strpos($server['buffer'], 'message of the day') !== false && !$this->joined) {
            $this->sendCommand($output, $server, "JOIN #NoAgenda\n\r");
        }
        if (strpos($server['buffer'], 'JOIN :#NoAgenda') !== false) {
            $this->joined = true;

            $output->writeln('<bg=green;fg=white>Starting crawling process</bg=green;fg=white>');
        }
    }

    protected function sendCommand(OutputInterface $output, array &$server, string $command)
    {
        fwrite($server['socket'], $command, strlen($command));
        $output->writeln(sprintf('[SEND] %s', trim($command)));
    }
}
