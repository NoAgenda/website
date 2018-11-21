<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ClearCrawlingDataCommand extends Command
{
    protected static $defaultName = 'app:clear-crawling-data';

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
            ->setDescription('Clears old crawling data')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Force deleting of old files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $io->title('No Agenda Data Cleaner');

        $this->clearDirectory($io, 'chat_logs', $save);
        $this->clearDirectory($io, 'livestream_recordings', $save);

        if ($save) {
            $io->success('The files have been removed.');
        }
        else {
            $io->note('The files have not been removed. Pass the `--save` option to save the results in the database.');
        }
    }

    private function clearDirectory(OutputStyle $io, string $path, bool $save)
    {
        $finder = (new Finder)
            ->files()
            ->date('> now - 14 days')
            ->in(implode('/', [$this->storagePath, $path]))
        ;

        $filesystem = new Filesystem;

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $io->writeln(sprintf('Removing file: %s', $file->getPathname()));

            if ($save) {
                $filesystem->remove($file->getPathname());
            }
        }
    }
}
