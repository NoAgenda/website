<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class CrawlAudioCommand extends Command
{
    protected static $defaultName = 'app:crawl-audio';

    protected function configure()
    {
        $this
            ->setDescription('Crawls a short clip from the livestream to match timestamps')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $time = (new \DateTimeImmutable())->format('YmdHis');

        $process = new Process(sprintf('bin/recorder %s', $time));
        $process->run();

        $io->success(sprintf('Created recording: %s', $time));
    }
}
