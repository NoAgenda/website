<?php

namespace App\Command;

use App\Crawling\ChatRecorder;
use App\Crawling\LivestreamRecorder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecordCommand extends Command
{
    protected static $defaultName = 'record';
    protected static $defaultDescription = 'The type of data to record: livestream, chat';

    public function __construct(
        private readonly ChatRecorder $chatRecorder,
        private readonly LivestreamRecorder $livestreamRecorder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition([
           new InputArgument('data', InputArgument::REQUIRED, 'The type of data to record: livestream, chat'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $input->getArgument('data');

        $actions = [
            'chat' => fn () => $this->chatRecorder->record(),
            'livestream' => fn () => $this->livestreamRecorder->record(),
        ];

        if (array_key_exists($data, $actions)) {
            $actions[$data]();

            return Command::SUCCESS;
        }

        $output->writeln("Invalid data type: $data");

        return Command::INVALID;
    }
}
