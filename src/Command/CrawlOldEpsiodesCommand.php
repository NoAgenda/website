<?php

namespace App\Command;

use App\Entity\Episode;
use App\Repository\EpisodeRepository;
use App\ShownotesParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class CrawlOldEpsiodesCommand extends Command
{
    protected static $defaultName = 'app:crawl-old-episodes';

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
    ){
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->parser = $parser;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Crawls old episodes from shownotes')
            ->addArgument('episode', InputArgument::REQUIRED, 'The episode code to start')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save processing results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $code = $input->getArgument('episode');

        $io->title('No Agenda Old Episode Crawler');

        $episodes = $this->episodeRepository->findAll();

        $numberedEpisodes = [];

        foreach ($episodes as $episode) {
            $numberedEpisodes[$episode->getCode()] = true;
        }

        for (; $code < 1049; $code++) {
            if (isset($numberedEpisodes[$code])) {
                continue;
            }

            $io->writeln("Crawling episode $code");

            $episode = new Episode();
            $episode->setCode($code);
            $episode->setAuthor('Adam Curry & John C. Dvorak');

            $shownotes = $this->parser->parse($episode, true);
            $episode->setShownotes($shownotes);

            if ($save) {
                $this->entityManager->persist($episode);
                $this->entityManager->flush();

                $io->success("Saved episode $code");

                $processor = $this->getHelper('process');
                $verbosity = $io->isDebug() ? '-vvv' : ($io->isVeryVerbose() ? '-vv' : ($io->isVerbose() ? '-v' : ''));
                $phpExecutable = (new ExecutableFinder)->find('php');

                $command = [
                    $phpExecutable,
                    'bin/console',
                    'app:crawl-files',
                    $episode->getCode(),
                    $save ? '--save' : null,
                    $verbosity
                ];
                $process = new Process($command);
                $process->setTimeout(600);
                $processor->run($io, $process, sprintf('An error occurred while fetching files for episode %s.', $episode->getCode()), null, OutputInterface::VERBOSITY_VERBOSE);
            }
        }

        return 0;
    }
}
