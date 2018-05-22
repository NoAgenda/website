<?php

namespace App\Command;

use App\Entity\Show;
use App\Entity\TranscriptLine;
use App\Repository\ShowRepository;
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
     * @var ShowRepository
     */
    private $showRepository;

    /**
     * @var TranscriptLineRepository
     */
    private $transcriptLineRepository;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, ShowRepository $showRepository, TranscriptLineRepository $transcriptLineRepository)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->showRepository = $showRepository;
        $this->transcriptLineRepository = $transcriptLineRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('show', InputArgument::REQUIRED, 'The show code')
            ->addArgument('uri', InputArgument::REQUIRED, 'URI to the transcript file')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save crawling results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $showCode = $input->getArgument('show');
        $show = $this->showRepository->findOneBy(['code' => $showCode]);

        if ($show === null) {
            $io->error(sprintf('Unknown show "%s".', $showCode));

            return;
        }

        if ($this->transcriptLineRepository->count(['show' => $show]) > 0) {
            $io->warning('This show already has transcript lines. This part of the application still needs work.');

            return;
        }

        $io->text('Crawling transcript file...');

        $output = (new TranscriptParser())->parse($input->getArgument('uri'));
        $result = count($output['lines']);

        $io->text(sprintf('Found %s transcript lines.', $result));

        foreach ($output['lines'] as $line) {
            $this->handleEntry($io, $line, $show, $save);
        }

        if ($save) {
            $this->entityManager->flush();

            $io->success(sprintf('Saved %s new transcript lines.', $result));
        }
        else {
            $io->note('The crawling results have not been saved. Pass the <info>--save</info> option to save the results in the database.');
        }
    }

    private function handleEntry(OutputStyle $io, array $entry, Show $show, $save)
    {
        $line = new TranscriptLine;

        $line->setShow($show);
        $line->setText($entry['text']);
        $line->setTimestamp($entry['timestamp']);
        $line->setDuration(0);
        $line->setCrawlerOutput($entry);

        if ($save) {
            $this->entityManager->persist($line);
        }
    }
}
