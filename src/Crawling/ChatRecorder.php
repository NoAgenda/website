<?php

namespace App\Crawling;

use Phergie\Irc\Client\React\Client;
use Phergie\Irc\Client\React\WriteStream;
use Phergie\Irc\Connection;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class ChatRecorder implements RecorderInterface
{
    use LoggerAwareTrait;

    private bool $joined = false;
    private \DateTime $lastUpdatedAt;

    public function __construct()
    {
        $this->lastUpdatedAt = new \DateTime();
        $this->logger = new NullLogger();
    }

    public function record(): void
    {
        $basePath = sprintf('%s/chat_logs', $_SERVER['APP_STORAGE_PATH']);

        (new Filesystem())->mkdir($basePath);

        $connection = (new Connection())
            ->setServerHostname('irc.zeronode.net')
            ->setServerPort(6667)
            ->setHostname('irc.zeronode.net')
            ->setServername('irc.zeronode.net')
            ->setNickname($nickname = self::getRandomName())
            ->setUsername($nickname)
            ->setRealname($nickname);

        $client = new Client();

        $client->on('irc.received', function (array $message, WriteStream $write) use ($basePath) {
            if (!$this->joined) {
                if (str_contains($message['message'], 'message of the day')) {
                    $write->ircJoin('#NoAgenda');

                    $this->joined = true;
                }

                return;
            }

            $this->lastUpdatedAt = $lastUpdatedAt = new \DateTime();

            if (!strpos($message['message'], 'PRIVMSG #NoAgenda')) {
                return;
            }

            $rawMessage = $message['message'];
            $rawMessage = preg_replace('/[[:cntrl:]]/', '', $rawMessage);
            $rawMessage = mb_convert_encoding($rawMessage, 'UTF-8', 'UTF-8');

            $path = sprintf('%s/%s.log', $basePath, $lastUpdatedAt->format('Ymd'));
            $log = sprintf('%s >>> %s', $lastUpdatedAt->format('Y-m-d H:i:s'), $rawMessage);

            file_put_contents($path, "$log\n", FILE_APPEND | LOCK_EX);

            $this->updateLivestreamInfo($rawMessage);
        });

        $client->on('irc.tick', function () {
            $ifNotUpdatedSince = (new \DateTime())->sub(new \DateInterval('PT15M'));

            if ($this->lastUpdatedAt < $ifNotUpdatedSince) {
                exit();
            }
        });

        $client->run($connection);
    }

    private function updateLivestreamInfo(string $rawMessage): void
    {
        $message = self::parseMessage($rawMessage);

        if ('Doug' !== $message['username'] || !str_starts_with($message['contents'], 'Now playing: ')) {
            return;
        }

        $nowPlaying = substr($message['contents'], strlen('Now playing: '));

        $info = [
            'now_playing' => $nowPlaying,
        ];

        $livestreamInfoPath = sprintf('%s/livestream_info.json', $_SERVER['APP_STORAGE_PATH']);
        file_put_contents($livestreamInfoPath, json_encode($info));
    }

    public static function getRandomName(): string
    {
        $adjectives = ['tiny', 'delicious', 'gentle', 'cool', 'brave', 'grumpy', 'fierce', 'angry'];
        $nouns = ['bee', 'pizza', 'chef', 'puppy', 'gnome', 'panda', 'koala'];

        return ucfirst($adjectives[array_rand($adjectives)]) . ucfirst($nouns[array_rand($nouns)]);
    }

    public static function parseMessage(string $rawMessage): ?array
    {
        preg_match('/:([^!]+)!([^@]+)@(\S+) PRIVMSG #NoAgenda :(.+)/', $rawMessage, $matches);

        if (!isset($matches[0])) {
            return null;
        }

        list(, $username, $client, $ip, $contents) = $matches;

        return [
            'username' => $username,
            'contents' => nl2br($contents),
        ];
    }
}
