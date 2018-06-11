<?php

namespace App\Command;

use App\Repository\ShowRepository;
use App\Repository\TranscriptLineRepository;
use App\TranscriptParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
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
            ->addOption('history', null, InputOption::VALUE_NONE, 'Go far back in history to retrieve transcripts')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save crawling results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $history = $input->getOption('history');
        $save = $input->getOption('save');

        $io->text('Crawling site for transcript files ...');

        $data = (new TranscriptParser())->crawl($history);
        $result = 0;

        foreach ($data as $code => $uri) {
            $command = $this->getApplication()->find('app:crawl-transcript');

            $input = new ArrayInput([
                'command' => 'app:crawl-transcript',
                'show' => $code,
                'uri' => $uri,
                '--save' => $save,
            ]);

            $returnCode = $command->run($input, $output);

            if ($returnCode === 0) {
                ++$result;
            }
        }

        if ($save) {
            $this->entityManager->flush();

            $io->success(sprintf('Saved %s new transcript entries.', $result));
        }
        else {
            $io->note('The crawling results have not been saved. Pass the `--save` option to save the results in the database.');
        }
    }
}
