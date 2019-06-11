<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption('identifier', null, InputOption::VALUE_OPTIONAL, 'The identifer to match the PID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        while (true) {
            $io->text('Recording livestream ...');

            $time = (new \DateTimeImmutable())->format('YmdHis');
            $path = sprintf('%s/livestream_recordings/recording_%s', $this->storagePath, $time);

            $command = [
                'bin/scripts/record-livestream.bash',
                $path,
            ];

            if ($output->isVerbose()) {
                $io->text('Executing command: ' . implode(' ', $command));
            }

            $process = new Process($command);
            $process->setTimeout(null);
            $returnCode = $process->run();

            if ($returnCode > 0) {
                $io->error($output->isVerbose() ? $process->getErrorOutput() : 'An error occurred while creating the recording.');

                return 1;
            }

            $io->success(sprintf('Created recording "%s".', $path));

            sleep(60 * 29);
        }

        return 1;
    }
}
