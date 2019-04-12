<?php

namespace App\Command;

use App\TranscriptParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class CrawlTranscriptsCommand extends Command
{
    protected static $defaultName = 'app:crawl-transcripts';

    protected function configure()
    {
        $this
            ->setDescription('Crawls the transcripts site for all transcript entries')
            ->addOption('history', null, InputOption::VALUE_NONE, 'Go far back in history to retrieve transcripts')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save crawling results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $history = $input->getOption('history');
        $save = $input->getOption('save');

        $io->title('No Agenda Transcripts Crawler');

        $data = (new TranscriptParser())->crawl($history);
        $result = 0;

        foreach ($data as $code => $uri) {
            $processor = $this->getHelper('process');
            $verbosity = $io->isDebug() ? '-vvv' : ($io->isVeryVerbose() ? '-vv' : ($io->isVerbose() ? '-v' : ''));
            $phpExecutable = (new ExecutableFinder)->find('php');

            $command = [
                $phpExecutable,
                'bin/console',
                'app:crawl-transcript',
                $code,
                $uri,
                $save ? '--save' : null,
                $verbosity
            ];
            $process = new Process($command);
            $processor->run($io, $process, sprintf('An error occurred while fetching the transcript for episode %s.', $code), null, OutputInterface::VERBOSITY_VERBOSE);

            if ($process->getExitCode() === 0) {
                ++$result;
            }
        }

        if ($save) {
            $io->success(sprintf('Saved %s new transcript entries.', $result));
        }
        else {
            $io->note('The crawling results have not been saved. Pass the `--save` option to save the results in the database.');
        }
    }
}
