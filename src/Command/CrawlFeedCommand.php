<?php

namespace App\Command;

use App\Entity\Show;
use App\FeedParser;
use App\Repository\ShowRepository;
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
     * @var ShowRepository
     */
    private $showRepository;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, ShowRepository $showRepository)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->showRepository = $showRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawls the No Agenda RSS feed')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save crawling results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $io->text('Crawling No Agenda RSS feed...');

        $output = (new FeedParser())->parse();
        $results = [
            self::ENTRY_EXISTS => 0,
            self::ENTRY_NEW => 0,
            self::ENTRY_UPDATED => 0,
        ];

        $entries = array_reverse($output['entries']);

        foreach ($entries as $entry) {
            $result = $this->handleEntry($io, $entry, $save);

            ++$results[$result];
        }

        if ($results[self::ENTRY_NEW] === 0 && $results[self::ENTRY_NEW] === 0) {
            $io->text('No changes');
        }

        if ($save) {
            $this->entityManager->flush();

            $io->success(sprintf('Found %s existing shows, saved %s new and %s updated shows.', $results[self::ENTRY_EXISTS], $results[self::ENTRY_NEW], $results[self::ENTRY_UPDATED]));
        }
        else {
            $io->note('The crawling results have not been saved. Pass the <fg=green>--save</fg=green> option to save the results in the database.');
        }
    }

    private function handleEntry(OutputStyle $io, array $entry, $save)
    {
        $show = $this->showRepository->findOneBy(['code' => $entry['code']]);

        $new = $show === null;

        if ($new) {
            $show = new Show;
        }

        if ($new || $show->getCrawlerOutput() != $entry) {
            $show->setCode($entry['code']);
            $show->setName($entry['name']);
            $show->setAuthor($entry['author']);
            $show->setPublishedAt($entry['publishedAt']);
            $show->setImageUri($entry['image']);
            $show->setAudioUri($entry['enclosure']->url);
            $show->setCrawlerOutput($entry);

            if ($save) {
                $this->entityManager->persist($show);
            }

            if ($new) {
                $io->text(sprintf('New show: %s', $show->getCode()));

                return self::ENTRY_NEW;
            }

            $io->text(sprintf('Updated show: %s', $show->getCode()));

            return self::ENTRY_UPDATED;
        }

        return self::ENTRY_EXISTS;
    }
}
