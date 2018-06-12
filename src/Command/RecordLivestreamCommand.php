<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class RecordLivestreamCommand extends Command
{
    protected static $defaultName = 'app:crawl-audio';

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
            ->setDescription('Crawls a short clip from the livestream to match timestamps')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $time = (new \DateTimeImmutable())->format('YmdHis');
        $path = sprintf('%s/show_chunks/short/%s', $this->storagePath, $time);

        $process = new Process(sprintf('bin/recorder.bash "%s"', $path));
        $process->run();

        $io->success(sprintf('Created recording: %s', $path));
    }
}
