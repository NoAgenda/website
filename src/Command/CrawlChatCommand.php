<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlChatCommand extends Command
{
    protected static $defaultName = 'app:crawl-chat';

    /**
     * @var string
     */
    private $storagePath;

    /**
     * @var \DateTimeImmutable
     */
    private $connectedAt;

    /**
     * @var boolean
     */
    private $joined;

    /**
     * @var integer
     */
    private $increment;

    public function __construct(?string $name = null, string $storagePath)
    {
        parent::__construct($name);

        $this->storagePath = $storagePath;
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
        $this->connectedAt = new \DateTimeImmutable;

        $config = [
            'server' => 'irc.zeronode.net',
            'port' => 6667,
            'nick' => $this->getRandomName(),
        ];

        $server = [];
        $server['socket'] = fsockopen($config['server'], $config['port'], $errno, $errstr, 2);

        if ($server['socket']) {
            $this->sendCommand($output, $server, "PASS NOPASS\n\r");
            $this->sendCommand($output, $server, "NICK " . $config['nick'] . "\n\r");
            $this->sendCommand($output, $server, "USER " . $config['nick'] . " USING THE TROLL FACTORY\n\r");

            while (!feof($server['socket'])) {
                $this->readServer($output, $server);
                flush();
                usleep(100000);
            }
        }
    }

    protected function readServer(OutputInterface $output, array &$server)
    {
        $server['buffer'] = fgets($server['socket'], 1024);

        if ($output->isVerbose()) {
            $output->writeln(sprintf('[RECEIVE] %s', trim($server['buffer'])));
        }

        // Ping Pong
        if (substr($server['buffer'], 0, 4) == 'PING') {
            $this->sendCommand($output, $server, "PONG :" . substr($server['buffer'], 6) . "\n\r");

            return;
        }

        // Save messages
        if ($this->joined) {
            $now = new \DateTimeImmutable;

            $logPath = sprintf('%s/chat_logs/%s.log', $this->storagePath, $now->format('Ymd'));

            $log = sprintf('%s >>> %s', $now->format('Y-m-d H:i:s'), trim($server['buffer']));

            file_put_contents($logPath, $log . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        // Join channel
        if (!$this->joined) {
            if (strpos($server['buffer'], 'message of the day') !== false) {
                $this->sendCommand($output, $server, "JOIN #NoAgenda\n\r");
            }
            if (strpos($server['buffer'], 'JOIN :#NoAgenda') !== false) {
                $this->joined = true;

                $output->writeln('<bg=green;fg=white>Starting crawling process</bg=green;fg=white>');
            }
        }
    }

    protected function sendCommand(OutputInterface $output, array &$server, string $command)
    {
        fwrite($server['socket'], $command, strlen($command));

        if ($output->isVerbose()) {
            $output->writeln(sprintf('[SEND] %s', trim($command)));
        }
    }

    protected function getRandomName(): string
    {
        $adjectives = [
            'tiny',
            'delicious',
            'gentle',
            'agreeable',
            'brave',
            'orange',
            'grumpy',
            'fierce',
            'victorious',
        ];
        $nouns = [
            'elephant',
            'pizza',
            'jellybean',
            'chef',
            'puppy',
            'gnome',
            'kangaroo',
        ];

        return sprintf('%s%s', ucfirst($adjectives[array_rand($adjectives)]), ucfirst($nouns[array_rand($nouns)]));
    }
}
