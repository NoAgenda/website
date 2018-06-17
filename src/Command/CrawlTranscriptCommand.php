<?php

namespace App\Command;

use App\Entity\Episode;
use App\Entity\TranscriptLine;
use App\Repository\EpisodeRepository;
use App\Repository\TranscriptLineRepository;
use App\TranscriptParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrawlTranscriptCommand extends Command
{
    protected static $defaultName = 'app:crawl-transcript';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EpisodeRepository
     */
    private $episodeRepository;

    /**
     * @var TranscriptLineRepository
     */
    private $transcriptLineRepository;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, EpisodeRepository $episodeRepository, TranscriptLineRepository $transcriptLineRepository)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->transcriptLineRepository = $transcriptLineRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawls a transcript file for a single episode')
            ->addArgument('episode', InputArgument::REQUIRED, 'The episode code')
            ->addArgument('uri', InputArgument::REQUIRED, 'URI to the transcript file')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save crawling results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $code = $input->getArgument('episode');
        $episode = $this->episodeRepository->findOneBy(['code' => $code]);

        if ($episode === null) {
            $io->error(sprintf('Unknown episode "%s".', $code));

            return;
        }

        if ($this->transcriptLineRepository->count(['episode' => $episode]) > 0) {
            $io->warning('This episode already has transcript lines. This part of the application still needs work.');

            return;
        }

        $io->text(sprintf('Crawling transcript file for episode %s ...', $code));

        $data = (new TranscriptParser())->parse($input->getArgument('uri'));
        $result = count($data['lines']);

        $io->text(sprintf('Found %s transcript lines.', $result));

        foreach ($data['lines'] as $line) {
            $this->handleEntry($io, $line, $episode, $save);
        }

        if ($save) {
            $this->entityManager->flush();

            $io->success(sprintf('Saved %s new transcript lines.', $result));
        }
        else {
            $io->note('The crawling results have not been saved. Pass the `--save` option to save the results in the database.');
        }
    }

    private function handleEntry(OutputStyle $io, array $entry, Episode $episode, $save)
    {
        $line = new TranscriptLine;

        $line->setEpisode($episode);
        $line->setText($entry['text']);
        $line->setTimestamp($entry['timestamp']);
        $line->setDuration(0);
        $line->setCrawlerOutput($entry);

        if ($save) {
            $this->entityManager->persist($line);
        }
    }
}
