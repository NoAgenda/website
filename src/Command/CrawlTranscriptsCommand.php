<?php

namespace App\Command;

use App\Repository\ShowRepository;
use App\Repository\TranscriptLineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrawlTranscriptsCommand extends Command
{
    protected static $defaultName = 'app:crawl-transcripts';

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
            ->setDescription('Crawls the transcripts site for all transcript entries')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save crawling results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        if ($save) {
            $this->entityManager->flush();

            $io->success(sprintf('Saved %s new transcript entries.', $result));
        }
        else {
            $io->note('The crawling results have not been saved. Pass the <fg=green>--save</fg=green> option to save the results in the database.');
        }
    }
}
