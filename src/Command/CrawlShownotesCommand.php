<?php

namespace App\Command;

use App\Repository\EpisodeRepository;
use App\ShownotesParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrawlShownotesCommand extends Command
{
    protected static $defaultName = 'app:crawl-shownotes';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EpisodeRepository
     */
    private $episodeRepository;

    /**
     * @var ShownotesParser
     */
    private $parser;

    public function __construct(
        ?string $name = null,
        EntityManagerInterface $entityManager,
        EpisodeRepository $episodeRepository,
        ShownotesParser $parser
    )
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->parser = $parser;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawls the shownotes for a episode')
            ->addArgument('episode', InputArgument::REQUIRED, 'The episode code')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force overwriting existing shownotes data')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save processing results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $force = $input->getOption('force');
        $save = $input->getOption('save');

        $code = $input->getArgument('episode');

        $io->title('No Agenda Shownotes Crawler');

        $episode = $this->episodeRepository->findOneBy(['code' => $code]);

        if ($episode === null) {
            $io->error(sprintf('Unknown episode "%s".', $code));

            return 1;
        }

        $shownotes = $this->parser->parse($episode);

        if ($force || $shownotes !== $episode->getShownotes()) {
            $episode->setShownotes($shownotes);

            if ($save) {
                $io->success('The shownotes data has been updated.');

                $this->entityManager->persist($episode);
                $this->entityManager->flush();
            }
            else {
                $io->note('The shownotes for this episode changed but the crawling results have not been saved. Pass the `--save` option to save the results in the database.');
            }

            return 0;

        }

        $io->text('The shownotes data for this episode has not changed.');

        return 0;
    }
}
