<?php

namespace App\Command;

use App\Message\CrawlBatSignal;
use App\Message\CrawlFeed;
use App\Message\CrawlTranscripts;
use App\Message\CrawlYoutube;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EnqueueCommand extends Command
{
    protected static $defaultName = 'app:enqueue';

    private $messenger;

    public function __construct(string $name = null, MessageBusInterface $messenger)
    {
        parent::__construct($name);

        $this->messenger = $messenger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Queues crawling jobs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $messages = [
            new CrawlBatSignal(),
            new CrawlFeed(),
            new CrawlTranscripts(),
            new CrawlYoutube(),
        ];

        foreach ($messages as $message) {
            $this->messenger->dispatch($message);
        }

        return 0;
    }
}
