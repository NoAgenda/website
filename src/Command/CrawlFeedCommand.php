<?php

namespace App\Command;

use App\Entity\Episode;
use App\Entity\EpisodePart;
use App\Entity\User;
use App\FeedParser;
use App\NotificationPublisher;
use App\Repository\EpisodePartRepository;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class CrawlFeedCommand extends Command
{
    const ENTRY_EXISTS = 0;
    const ENTRY_NEW = 1;
    const ENTRY_UPDATED = 2;

    protected static $defaultName = 'app:crawl-feed';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EpisodeRepository
     */
    private $episodeRepository;

    /**
     * @var EpisodePartRepository
     */
    private $episodePartRepository;

    /**
     * @var NotificationPublisher
     */
    private $notificationPublisher;

    /**
     * @var string
     */
    private $projectPath;

    /**
     * @var string
     */
    private $storagePath;

    public function __construct(
        ?string $name = null,
        EntityManagerInterface $entityManager,
        EpisodeRepository $episodeRepository,
        EpisodePartRepository $episodePartRepository,
        NotificationPublisher $notificationPublisher,
        string $projectPath,
        string $storagePath
    )
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->episodePartRepository = $episodePartRepository;
        $this->notificationPublisher = $notificationPublisher;
        $this->projectPath = $projectPath;
        $this->storagePath = $storagePath;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawls the No Agenda RSS feed')
            ->addOption('files', null, InputOption::VALUE_NONE, 'Download related episode files')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save crawling results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $files = $input->getOption('files');
        $save = $input->getOption('save');

        $io->title('No Agenda RSS Feed Crawler');

        $feedOutput = (new FeedParser())->parse();
        $results = [
            self::ENTRY_EXISTS => 0,
            self::ENTRY_NEW => 0,
            self::ENTRY_UPDATED => 0,
        ];

        $entries = array_reverse($feedOutput['entries']);

        foreach ($entries as $entry) {
            $result = $this->handleEntry($io, $entry, $files, $save);

            ++$results[$result];
        }

        if ($results[self::ENTRY_NEW] === 0 && $results[self::ENTRY_UPDATED] === 0) {
            $io->text('No changes');
        }

        if ($save) {
            $io->success(sprintf('Found %s existing episodes, saved %s new and %s updated episodes.', $results[self::ENTRY_EXISTS], $results[self::ENTRY_NEW], $results[self::ENTRY_UPDATED]));
        }
        else {
            $io->note('The crawling results have not been saved. Pass the `--save` option to save the results in the database.');
        }
    }

    private function handleEntry(OutputStyle $io, array $entry, bool $files, bool $save)
    {
        $episode = $this->episodeRepository->findOneBy(['code' => $entry['code']]);

        $new = $episode === null;

        if ($new) {
            $episode = new Episode;
        }

        $updated = $new || $episode->getCrawlerOutput() != $entry;

        if ($updated) {
            $episode
                ->setCode($entry['code'])
                ->setName($entry['name'])
                ->setAuthor($entry['author'])
                ->setPublishedAt($entry['publishedAt'])
                ->setCoverUri($entry['coverUri'])
                ->setRecordingUri($entry['recordingUri'])
                ->setCrawlerOutput($entry)
            ;

            $part = null;

            if (!$episode->isPersisted()) {
                $part = (new EpisodePart)
                    ->setEpisode($episode)
                    ->setCreator($this->entityManager->find(User::class, 1))
                    ->setName('Start of Show')
                    ->setStartsAt(0)
                ;
            }

            $io->text(sprintf('%s episode: %s', $new ? 'New' : 'Updated', $episode->getCode()));

            if ($save) {
                if ($new) {
                    $this->notificationPublisher->publishEpisode($episode);
                }

                $this->entityManager->persist($episode);

                if ($part) {
                    $this->entityManager->persist($part);
                }

                // Flush entities in advance of retrieving files
                $this->entityManager->flush();
            }
        }

        if ($save && $files) {
            $io->text(sprintf('Fetching files for episode: %s', $episode->getCode()));

            $processor = $this->getHelper('process');
            $verbosity = $io->isDebug() ? '-vvv' : ($io->isVeryVerbose() ? '-vv' : ($io->isVerbose() ? '-v' : ''));
            $phpExecutable = (new ExecutableFinder)->find('php');

            $command = sprintf('%s bin/console app:crawl-files %s %s %s', $phpExecutable, $episode->getCode(), $save ? '--save' : '', $verbosity);
            $process = new Process($command);
            $process->setTimeout(600);
            $processor->run($io, $process, sprintf('An error occurred while fetching files for episode %s.', $episode->getCode()), null, OutputInterface::VERBOSITY_VERBOSE);
        }

        if ($updated) {
            return $new ? self::ENTRY_NEW : self::ENTRY_UPDATED;
        }

        return self::ENTRY_EXISTS;
    }
}
