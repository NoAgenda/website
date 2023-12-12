<?php

namespace App\Command;

use App\Crawling\CrawlingProcessor;
use App\Crawling\CrawlingResult;
use App\Crawling\EpisodeCrawlerInterface;
use App\Crawling\EpisodeFileCrawlerInterface;
use App\Entity\Episode;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrawlCommand extends Command
{
    protected static $defaultName = 'crawl';
    protected static $defaultDescription = 'Execute a crawling command';

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly EpisodeRepository $episodeRepository,
        protected readonly CrawlingProcessor $crawlingProcessor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('data', InputArgument::OPTIONAL, 'The type of data to crawl (help for more information)', 'help')
            ->addOption('episode', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'The episode code to crawl')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Crawl all episodes in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        if ('help' === $data = $input->getArgument('data')) {
            $style->writeln('Available types of data:');
            $style->listing(array_keys(CrawlingProcessor::$crawlerClasses));

            return Command::SUCCESS;
        }

        if (!$crawlerName = CrawlingProcessor::$crawlerClasses[$data] ?? false) {
            $style->warning(sprintf('Invalid data type: %s', $data));

            return Command::INVALID;
        }

        $jobs = [];

        if (is_subclass_of($crawlerName, EpisodeCrawlerInterface::class) || is_subclass_of($crawlerName, EpisodeFileCrawlerInterface::class)) {
            $episodeCodes = $input->getOption('all')
                ? $this->entityManager->createQuery('select na.code from ' . Episode::class . ' na')->getSingleColumnResult()
                : $input->getOption('episode');

            if (!count($episodeCodes)) {
                $style->warning('To crawl this type of data you need to specify an episode with --episode [code] or --all.');

                return Command::INVALID;
            }

            foreach ($episodeCodes as $code) {
                $episode = $this->episodeRepository->findOneByCode($code);

                if (!$episode) {
                    $style->warning(sprintf('Invalid episode code: %s', $code));

                    return Command::INVALID;
                }

                $jobs[] = $episode;
            }
        } else {
            $jobs[] = null;
        }

        $this->preCrawl();

        $results = [];

        foreach ($jobs as $episode) {
            if ($episode) {
                $results[] = $this->crawlEpisode($data, $episode, $style);
            } else {
                $results[] = $this->crawl($data, $style);
            }
        }

        $this->postCrawl($results, $style);

        return Command::SUCCESS;
    }

    protected function preCrawl(): void
    {
        $this->entityManager->beginTransaction();
    }

    protected function postCrawl(array $results, StyleInterface $style): void
    {
        $this->entityManager->flush();
        $this->entityManager->commit();

        $faultyResults = 0;

        foreach ($results as $result) {
            if ($result instanceof CrawlingResult && !$result->success) {
                $faultyResults++;
            }
        }

        if ($faultyResults > 0) {
            $style->warning(sprintf('Crawling finished with %s error(s)', $faultyResults));
        } else {
            $style->success('Crawling finished');
        }
    }

    protected function crawl(string $data, StyleInterface $style): ?CrawlingResult
    {
        $style->info(sprintf('Crawling %s...', $data));

        $result = $this->crawlingProcessor->crawl($data);

        $style->writeln('');

        return $result;
    }

    protected function crawlEpisode(string $data, Episode $episode, StyleInterface $style): ?CrawlingResult
    {
        $style->info(sprintf('Crawling %s for episode %s...', $data, $episode->getCode()));

        $result = $this->crawlingProcessor->crawl($data, $episode);

        $style->writeln('');

        return $result;
    }
}
