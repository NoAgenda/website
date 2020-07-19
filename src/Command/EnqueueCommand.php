<?php

namespace App\Command;

use App\Message\CrawlBatSignal;
use App\Message\CrawlFeed;
use App\Message\CrawlTranscripts;
use App\Message\CrawlYoutube;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
            ->addArgument('data', InputArgument::REQUIRED, 'The type of job to enqueue: bat_signal, feed, transcripts, youtube')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $input->getArgument('data');

        $messages = [
            'bat_signal' => new CrawlBatSignal(),
            'feed' => new CrawlFeed(),
            'transcripts' => new CrawlTranscripts(),
            'youtube' => new CrawlYoutube(),
        ];

        if (!isset($messages[$data])) {
            $output->writeln("Invalid data type: $data");
        }

        $this->messenger->dispatch($messages[$data]);

        return 0;
    }
}
