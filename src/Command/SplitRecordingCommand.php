<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class SplitRecordingCommand extends Command
{
    protected static $defaultName = 'app:split-recording';

    /**
     * @var string
     */
    private $storagePath;

    public function __construct(?string $name = null, string $storagePath)
    {
        parent::__construct($name);

        $this->storagePath = $storagePath;
    }

    protected function configure()
    {
        $this
            ->setDescription('Splits a show into smaller chunks to match timestamps')
            ->addArgument('show', InputArgument::REQUIRED, 'The show code')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $code = $input->getArgument('show');
        $sourcePath = sprintf('%s/shows/%s.mp3', $this->storagePath, $code);
        $targetPath = sprintf('%s/show_chunks/long/%s_', $this->storagePath, $code);

        $cmd = sprintf('bin/splitter.bash "%s" "%s"', $sourcePath, $targetPath);

        if ($output->isVerbose()) {
            $io->text('Executing command: ' . $cmd);
        }

        $process = new Process($cmd, null, null, null, null);
        $process->run();

        $io->success('Done splitting recording.');

        // todo get length of show
        // split files into segments of 10 minutes, seperated by 5 minutes...
        // first only first hour lol
        // find highest score, (make sure it matches in 2 seperate files?)
        // >>MATCH<<
    }
}
