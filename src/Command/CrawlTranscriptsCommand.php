<?php

namespace App\Command;

use App\TranscriptParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            $command = $this->getApplication()->find('app:crawl-transcript');

            $input = new ArrayInput([
                'command' => 'app:crawl-transcript',
                'episode' => $code,
                'uri' => $uri,
                '--save' => $save,
            ]);

            $returnCode = $command->run($input, $output);

            if ($returnCode === 0) {
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
