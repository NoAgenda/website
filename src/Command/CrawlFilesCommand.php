<?php

namespace App\Command;

use App\Entity\Show;
use App\Repository\ShowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class CrawlFilesCommand extends Command
{
    protected static $defaultName = 'app:crawl-files';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ShowRepository
     */
    private $showRepository;

    /**
     * @var string
     */
    private $storagePath;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, ShowRepository $showRepository, string $storagePath)
    {
        parent::__construct($name);

        $this->showRepository = $showRepository;
        $this->storagePath = $storagePath;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawls the media files for a show')
            ->addArgument('show', InputArgument::REQUIRED, 'The show code')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force crawling already processed files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $force = $input->getOption('force');

        $code = $input->getArgument('show');
        $show = $this->showRepository->findOneBy(['code' => $code]);

        if ($show === null) {
            $io->error(sprintf('Unknown show "%s".', $code));

            return;
        }

        $recordingPath = sprintf('%s/recordings/%s.mp3', $this->storagePath, $show->getCode());
        $coverPath = sprintf('%s/covers/%s.png', $this->storagePath, $show->getCode());

        // todo move creation of directories to setup script
        $filesystem = new Filesystem;
        $filesystem->mkdir([
            implode('/', [$this->storagePath, 'recordings']),
            implode('/', [$this->storagePath, 'covers']),
        ]);

        if ($force || !file_exists($recordingPath)) {
            $io->text(sprintf('Processing recording file for show %s ...', $show->getCode()));

            $this->handleRecordingFile($input, $output, $show, $recordingPath);
        }

        if ($force || !file_exists($coverPath)) {
            $io->text(sprintf('Processing cover file for show %s ...', $show->getCode()));

            $this->handleCoverFile($input, $output, $show, $coverPath);
        }

        $io->success('Files have been downloaded.');
    }

    private function handleCoverFile(InputInterface $input, OutputInterface $output, Show $show, $targetPath)
    {
        file_put_contents($targetPath, fopen($show->getImageUri(), 'r'));
    }

    private function handleRecordingFile(InputInterface $input, OutputInterface $output, Show $show, $targetPath)
    {
        $io = new SymfonyStyle($input, $output);

        // Download file
        file_put_contents($targetPath, fopen($show->getAudioUri(), 'r'));

        // Grab recording duration
        $duration = 0;

        $cmd = sprintf('ffmpeg -i %s 2>&1 | grep "Duration"', $targetPath);

        if ($output->isVerbose()) {
            $io->text('Executing command: ' . $cmd);
        }

        $process = new Process($cmd, null, null, null, null);
        $process->run();

        $output = $process->getOutput();

        if ($output !== '') {
            preg_match("/Duration: (\d+):(\d+):(\d+)/", $output, $matches);
            list(, $hours, $minutes, $seconds) = $matches;

            $duration = $seconds + ($minutes * 60) + ($hours * 60 * 60);
        }

        if ($duration !== 0) {
            $show->setDuration($duration);
            $io->text('debug: ' . $duration);
        }
        else {
            $io->error('Unable to retrieve recording duration.');
        }
    }
}
