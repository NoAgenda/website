<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class RunCommand extends Command
{
    protected static $defaultName = 'app:run';

    protected function configure()
    {
        $this
            ->setDescription('Executes the entire workflow of the application')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $io->title('No Agenda Workflow');

        if (!$save) {
            $io->note('Please make sure you know what you\'re doing before executing this command. Pass the `--save` option to execute this command.');

            return;
        }

        $processor = $this->getHelper('process');
        $verbosity = $io->isDebug() ? '-vvv' : ($io->isVeryVerbose() ? '-vv' : ($io->isVerbose() ? '-v' : ''));
        $phpExecutable = (new ExecutableFinder)->find('php');

        $io->text('> Crawl feed');

        // Crawl RSS feed including episode files
        $command = sprintf('%s bin/console app:crawl-feed --files --save %s', $phpExecutable, $verbosity);
        $process = new Process($command);
        $process->setTimeout(1500);
        $processor->run($io, $process, 'An error occurred while crawling the RSS feed.', null, OutputInterface::VERBOSITY_NORMAL);
        $io->newLine();

        $io->text('> Crawl transcripts');

        // Crawl transcripts
        $command = sprintf('%s bin/console app:crawl-transcripts --save %s', $phpExecutable, $verbosity);
        $process = new Process($command);
        $process->setTimeout(300);
        $processor->run($io, $process, 'An error occurred while crawling the transcripts.', null, OutputInterface::VERBOSITY_NORMAL);
        $io->newLine();

        $io->text('> Crawl the latest bat signal');

        // Crawl bat signal
        $command = sprintf('%s bin/console app:crawl-bat-signal --save %s', $phpExecutable, $verbosity);
        $process = new Process($command);
        $processor->run($io, $process, 'An error occurred while crawling for a bat signal.', null, OutputInterface::VERBOSITY_NORMAL);
        $io->newLine();

        $io->text('> Process the latest bat signal');

        // Process bat signal
        $command = sprintf('%s bin/console app:process-bat-signal --latest --save %s', $phpExecutable, $verbosity);
        $process = new Process($command);
        $process->setTimeout(3300);
        $processor->run($io, $process, 'An error occurred while processing a bat signal.', null, OutputInterface::VERBOSITY_NORMAL);
        $io->newLine();

        $io->text('> Clear old crawling data');

        // Remove old crawling data
        $command = sprintf('%s bin/console app:clear-crawling-data --save %s', $phpExecutable, $verbosity);
        $process = new Process($command);
        $process->setTimeout(300);
        $processor->run($io, $process, 'An error occurred while clearing old crawling data.', null, OutputInterface::VERBOSITY_NORMAL);
        $io->newLine();

        $io->success('Finished executing No Agenda workflow.');
    }
}
