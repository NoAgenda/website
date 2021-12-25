<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ClearCommand extends Command
{
    protected static $defaultName = 'app:clear';

    protected function configure(): void
    {
        $this
            ->setDescription('Clears old crawling data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->clearDirectory($io, 'chat_logs');
        $this->clearDirectory($io, 'livestream_recordings');

        $io->success('Old crawling data has been removed.');

        return 0;
    }

    private function clearDirectory(OutputStyle $io, string $path): void
    {
        $finder = Finder::create()
            ->files()
            ->date('< now - 90 days')
            ->in(implode('/', [$_SERVER['APP_STORAGE_PATH'], $path]))
        ;

        $filesystem = new Filesystem();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $io->writeln(sprintf('Removing file: %s', $file->getPathname()));

            $filesystem->remove($file->getPathname());
        }
    }
}
