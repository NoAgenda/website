<?php

namespace App\Command;

use App\Entity\Episode;
use App\Repository\EpisodeRepository;
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

    public function __construct(
        private readonly EpisodeRepository $episodeRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $files = [...$this->findOldChatLogs(), ...$this->findOldLivestreamRecordings()];
        $this->removeFiles($files, $style);

        $style->success('Old crawling data has been removed.');

        return Command::SUCCESS;
    }

    private function findOldChatLogs(): array
    {
        $recordingDates = array_filter(array_map(function (Episode $episode) {
            return (int) $episode->getRecordedAt()->format('Ymd');
        }, $this->episodeRepository->findAll()));

        $recordedAfter = (int) (new \DateTime())->sub(new \DateInterval('P3D'))->format('Ymd');

        $files = (new Finder())
            ->files()
            ->in($_SERVER['APP_STORAGE_PATH'].'/chat_logs')
            ->filter(function(\SplFileInfo $file) use ($recordedAfter, $recordingDates) {
                $recordingDate = (int) $file->getFilenameWithoutExtension();

                if (in_array($recordingDate, $recordingDates)) {
                    return false;
                }

                return $recordingDate < $recordedAfter;
            });

        return iterator_to_array($files);
    }

    private function findOldLivestreamRecordings(): array
    {
        $files = (new Finder())
            ->files()
            ->in($_SERVER['APP_STORAGE_PATH'].'/livestream_recordings')
            ->date('< now - 2 days');

        return iterator_to_array($files);
    }

    private function removeFiles(array $files, OutputStyle $style): void
    {
        $filesystem = new Filesystem();

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $style->writeln(sprintf('Removing file: %s', $file->getPathname()));

            $filesystem->remove($file->getPathname());
        }

        $style->success(sprintf('Files removed: %s', count($files)));
    }
}
