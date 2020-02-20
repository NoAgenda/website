<?php

namespace App\Command;

use App\Crawling\ChatRecorder;
use App\Crawling\CrawlingLogger;
use App\Crawling\LivestreamRecorder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

class RecordCommand extends Command implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    protected static $defaultName = 'app:record';

    protected function configure(): void
    {
        $this
            ->setDescription('Execute a crawling command')
            ->addArgument('data', InputArgument::REQUIRED, 'The type of data to record: livestream, chat')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $input->getArgument('data');

        $this->crawlingLogger()->addLogger(new ConsoleLogger($output));

        $actions = [
            'chat' => function () use ($output) {
                $recorder = $this->chatRecorder();

                $recorder->record();
            },
            'livestream' => function () use ($output) {
                $recorder = $this->livestreamRecorder();

                $recorder->record();
            },
        ];

        if (isset($actions[$data])) {
            $actions[$data]();

            return 0;
        }

        $output->writeln("Invalid data type: $data");

        return 1;
    }

    private function chatRecorder(): ChatRecorder
    {
        return $this->container->get(__METHOD__);
    }

    private function livestreamRecorder(): LivestreamRecorder
    {
        return $this->container->get(__METHOD__);
    }

    private function crawlingLogger(): CrawlingLogger
    {
        return $this->container->get(__METHOD__);
    }
}
