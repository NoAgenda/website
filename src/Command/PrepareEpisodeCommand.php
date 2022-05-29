<?php

namespace App\Command;

use App\Crawling\EpisodeProcessor;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PrepareEpisodeCommand extends Command
{
    protected static $defaultName = 'prepare';
    protected static $defaultDescription = 'Prepare an episode for publication';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected EpisodeRepository $episodeRepository,
        protected EpisodeProcessor $episodeProcessor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition([
            new InputArgument('episode', InputArgument::REQUIRED, 'The episode code to crawl'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $episode = $this->episodeRepository->findOneByCode($code = $input->getArgument('episode'));

        if (!$episode) {
            $style->warning(sprintf('Invalid episode code: %s', $code));

            return Command::INVALID;
        }

        $this->episodeProcessor->prepare($episode);

        return Command::SUCCESS;
    }
}
