<?php

namespace App\Crawling;

use Phergie\Irc\Client\React\Client;
use Phergie\Irc\Client\React\WriteStream;
use Phergie\Irc\Connection;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class ChatRecorder
{
    use LoggerAwareTrait;

    private $joined = false;

    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->lastUpdated = new \DateTime();
    }

    public function record(): void
    {
        $logPath = sprintf('%s/chat_logs', $_SERVER['APP_STORAGE_PATH']);

        if (!is_dir($logPath)) {
            $filesystem = new Filesystem();
            $filesystem->mkdir($logPath);
        }

        $connection = new Connection();

        $connection
            ->setServerHostname('irc.zeronode.net')
            ->setServerPort(6667)
            ->setHostname('irc.zeronode.net')
            ->setServername('irc.zeronode.net')
            ->setNickname($nickname = $this->getRandomName())
            ->setUsername($nickname)
            ->setRealname($nickname)
        ;

        $client = new Client();

        $client->on('irc.received', function ($message, WriteStream $write) {
            if (!$this->joined) {
                if (strpos($message['message'], 'message of the day') !== false) {
                    $write->ircJoin('#NoAgenda');

                    $this->joined = true;
                }

                return;
            }

            $this->lastUpdated = $lastUpdated = new \DateTime();

            if (!strpos($message['message'], 'PRIVMSG #NoAgenda')) {
                return;
            }

            $messageText = $message['message'];
            $messageText = preg_replace('/[[:cntrl:]]/', '', $messageText);
            $messageText = mb_convert_encoding($messageText, 'UTF-8', 'UTF-8');

            $log = sprintf('%s >>> %s', $lastUpdated->format('Y-m-d H:i:s'), $messageText);

            file_put_contents($this->getLogPath(), $log . "\n", FILE_APPEND | LOCK_EX);
        });

        $client->on('irc.tick', function () {
            $stallTime = (new \DateTime())->sub(new \DateInterval('PT15M'));

            if ($this->lastUpdated < $stallTime) {
                exit();
            }
        });

        $client->run($connection);
    }

    private function getLogPath(): string
    {
        return sprintf('%s/chat_logs/%s.log', $_SERVER['APP_STORAGE_PATH'], (new \DateTime())->format('Ymd'));
    }

    private function getRandomName(): string
    {
        $adjectives = [
            'tiny',
            'delicious',
            'gentle',
            'cool',
            'brave',
            'grumpy',
            'fierce',
            'angry',
        ];
        $nouns = [
            'bee',
            'pizza',
            'chef',
            'puppy',
            'gnome',
            'panda',
            'koala',
        ];

        return sprintf('%s%s', ucfirst($adjectives[array_rand($adjectives)]), ucfirst($nouns[array_rand($nouns)]));
    }
}
