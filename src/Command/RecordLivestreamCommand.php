<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class RecordLivestreamCommand extends Command
{
    protected static $defaultName = 'app:record-livestream';

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
            ->setDescription('Crawls an audio clip from the livestream to match chat timestamps')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->text('Recording livestream ...');

        $time = (new \DateTimeImmutable())->format('YmdHis');
        $path = sprintf('%s/livestream_recordings/recording_%s', $this->storagePath, $time);

        $cmd = sprintf('bin/scripts/record-livestream.bash "%s"', $path);

        if ($output->isVerbose()) {
            $io->text('Executing command: ' . $cmd);
        }

        $process = new Process($cmd);
        $process->setTimeout(null);
        $returnCode = $process->run();

        if ($returnCode > 0) {
            $io->error($output->isVerbose() ? $process->getErrorOutput() : 'An error occurred while creating the recording.');

            return;
        }

        $io->success(sprintf('Created recording "%s".', $path));
    }
}