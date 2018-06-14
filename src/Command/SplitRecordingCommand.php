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
            ->setDescription('Splits a recording into smaller chunks to match timestamps')
            ->addArgument('episode', InputArgument::REQUIRED, 'The episode code')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $code = $input->getArgument('episode');

        $sourcePath = sprintf('%s/recordings/%s.mp3', $this->storagePath, $code);
        $targetPath = sprintf('%s/audio_chunks/long/%s_', $this->storagePath, $code);

        $process = new Process(sprintf('bin/splitter.bash "%s" "%s"', $sourcePath, $targetPath));
        $process->run();

        $io->success('Done splitting recording.');
    }
}
