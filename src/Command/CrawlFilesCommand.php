<?php

namespace App\Command;

use App\Entity\Episode;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager as ImagineCacheManager;
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
     * @var EpisodeRepository
     */
    private $episodeRepository;

    /**
     * @var ImagineCacheManager
     */
    private $imagineCacheManager;

    /**
     * @var string
     */
    private $storagePath;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, EpisodeRepository $episodeRepository, ImagineCacheManager $imagineCacheManager, string $storagePath)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->storagePath = $storagePath;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawls the media files for a episode')
            ->addArgument('episode', InputArgument::REQUIRED, 'The episode code')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force download of existing files')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save processing results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $force = $input->getOption('force');
        $save = $input->getOption('save');

        $code = $input->getArgument('episode');

        $io->title('No Agenda File Crawler');

        $episode = $this->episodeRepository->findOneBy(['code' => $code]);

        if ($episode === null) {
            $io->error(sprintf('Unknown episode "%s".', $code));

            return 1;
        }

        $recordingPath = sprintf('%s/episode_recordings/%s.mp3', $this->storagePath, $episode->getCode());
        $coverPath = sprintf('%s/episode_covers/%s.png', $this->storagePath, $episode->getCode());

        // todo move creation of directories to setup script
        $filesystem = new Filesystem;
        $filesystem->mkdir([
            implode('/', [$this->storagePath, 'episode_recordings']),
            implode('/', [$this->storagePath, 'episode_covers']),
        ]);

        if ($force || !file_exists($recordingPath)) {
            $io->text(sprintf('Downloading recording file for episode %s ...', $episode->getCode()));

            $this->downloadRecordingFile($input, $output, $episode, $recordingPath);
        }

        $this->handleRecordingFile($input, $output, $episode, $recordingPath);

        if ($force || !file_exists($coverPath)) {
            $io->text(sprintf('Downloading cover file for episode %s ...', $episode->getCode()));

            $this->downloadCoverFile($input, $output, $episode, $coverPath);

            $this->imagineCacheManager->remove(sprintf('%s.png', $episode->getCode()));
        }

        if ($save) {
            $io->success('The files have been downloaded and processed.');

            $this->entityManager->persist($episode);
            $this->entityManager->flush();
        }
        else {
            $io->note('The files have been downloaded but processing results have not been saved. Pass the `--save` option to save the results in the database.');
        }

        return 0;
    }

    private function downloadCoverFile(InputInterface $input, OutputInterface $output, Episode $episode, $targetPath)
    {
        file_put_contents($targetPath, fopen($episode->getCoverUri(), 'r'));
    }

    private function downloadRecordingFile(InputInterface $input, OutputInterface $output, Episode $episode, $targetPath)
    {
        $io = new SymfonyStyle($input, $output);

        // Download file
        file_put_contents($targetPath, fopen($episode->getRecordingUri(), 'r'));
    }

    private function handleRecordingFile(InputInterface $input, OutputInterface $output, Episode $episode, $targetPath)
    {
        $io = new SymfonyStyle($input, $output);

        // Grab recording duration
        $duration = 0;

        $cmd = sprintf('ffmpeg -i %s 2>&1 | grep "Duration"', $targetPath);

        if ($output->isVerbose()) {
            $io->text('Executing command: ' . $cmd);
        }

        $process = new Process($cmd, null, null, null, null);
        $process->run();

        $durationOutput = $process->getOutput();

        if ($durationOutput !== '') {
            preg_match("/Duration: (\d+):(\d+):(\d+)/", $durationOutput, $matches);
            list(, $hours, $minutes, $seconds) = $matches;

            $duration = $seconds + ($minutes * 60) + ($hours * 60 * 60);
        }

        if ($duration !== 0) {
            $episode->setDuration($duration);

            if ($output->isVerbose()) {
                $io->text(sprintf('Updated duration of recording to %s.', $duration));
            }
        }
        else {
            $io->error('Unable to retrieve recording duration.');
        }
    }
}
