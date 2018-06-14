<?php

namespace App\Command;

use App\Entity\Episode;
use App\FeedParser;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

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
     * @var string
     */
    private $storagePath;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, EpisodeRepository $episodeRepository, string $storagePath)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
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

        $io->text('Crawling No Agenda RSS feed ...');

        $output = (new FeedParser())->parse();
        $results = [
            self::ENTRY_EXISTS => 0,
            self::ENTRY_NEW => 0,
            self::ENTRY_UPDATED => 0,
        ];

        $entries = array_reverse($output['entries']);

        foreach ($entries as $entry) {
            $result = $this->handleEntry($io, $entry, $files, $save);

            ++$results[$result];
        }

        if ($results[self::ENTRY_NEW] === 0 && $results[self::ENTRY_NEW] === 0) {
            $io->text('No changes');
        }

        if ($save) {
            $this->entityManager->flush();

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

        if ($new || $episode->getCrawlerOutput() != $entry) {
            $episode->setCode($entry['code']);
            $episode->setName($entry['name']);
            $episode->setAuthor($entry['author']);
            $episode->setPublishedAt($entry['publishedAt']);
            $episode->setCoverUri($entry['coverUri']);
            $episode->setRecordingUri($entry['recordingUri']);
            $episode->setCrawlerOutput($entry);

            if ($save) {
                $this->entityManager->persist($episode);
            }

            if ($new) {
                $io->text(sprintf('New episode: %s', $episode->getCode()));

                return self::ENTRY_NEW;
            }

            $io->text(sprintf('Updated episode: %s', $episode->getCode()));

            return self::ENTRY_UPDATED;
        }

        if ($files) {
            // $this->handleFiles($io, $episode);
        }

        return self::ENTRY_EXISTS;
    }
}
