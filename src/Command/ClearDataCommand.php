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

class ClearDataCommand extends Command
{
    protected static $defaultName = 'clear-data';
    protected static $defaultDescription = 'Clears old crawling data';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $this->clearDirectory($style, 'chat_logs');
        $this->clearDirectory($style, 'livestream_recordings');

        $style->success('Old crawling data has been removed.');

        return Command::SUCCESS;
    }

    private function clearDirectory(OutputStyle $style, string $path): void
    {
        $files = (new Finder())
            ->files()
            ->date('< now - 28 days')
            ->in(implode('/', [$_SERVER['APP_STORAGE_PATH'], $path]))
        ;

        $filesystem = new Filesystem();

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $style->writeln(sprintf('Removing file: %s', $file->getPathname()));

            $filesystem->remove($file->getPathname());
        }
    }
}
