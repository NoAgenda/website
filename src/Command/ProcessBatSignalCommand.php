<?php

namespace App\Command;

use App\Repository\BatSignalRepository;
use App\Repository\EpisodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ProcessBatSignalCommand extends Command
{
    protected static $defaultName = 'app:process-bat-signal';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EpisodeRepository
     */
    private $episodeRepository;

    /**
     * @var BatSignalRepository
     */
    private $signalRepository;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, EpisodeRepository $episodeRepository, BatSignalRepository $signalRepository)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->signalRepository = $signalRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Processes a bat signal')
            ->addArgument('episode', InputArgument::OPTIONAL, 'The episode code')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save processing results in the database')
            ->addOption('latest', null, InputOption::VALUE_NONE, 'Automatically find the bat signal for the latest episode')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $code = $input->getArgument('episode');
        $latest = $input->getOption('latest');

        $io->title('No Agenda Bat Signal Processor');

        if ($code == null && $latest) {
            $signal = $this->signalRepository->findOneByLatestEpisode();
        }
        elseif ($code) {
            $signal = $this->signalRepository->findOneByEpisode($code);
        }
        else {
            $io->error('Unable to determine bat signal source. Pass the `--latest` option to get started.');

            return 1;
        }

        if ($signal === null) {
            $io->error('No matching bat signal was found.');

            return $latest ? 0 : 1;
        }

        if ($signal->isProcessed()) {
            $io->error('The matched bat signal has already been processed.');

            return $latest ? 0 : 1;
        }

        $processor = $this->getHelper('process');
        $verbosity = $io->isDebug() ? '-vvv' : ($io->isVeryVerbose() ? '-vv' : ($io->isVerbose() ? '-v' : ''));
        $phpExecutable = (new ExecutableFinder)->find('php');

        // Match recording time
        $command = [
            $phpExecutable,
            'bin/console',
            'app:match-recording-time',
            $signal->getCode(),
            $save ? '--save' : null,
            $verbosity
        ];
        $process = new Process($command);
        $process->setTimeout(3000);
        $processor->run($io, $process, 'An error occurred while matching the recording time.', null, OutputInterface::VERBOSITY_NORMAL);
        $io->newLine();

        // Match chat messages
        $command = [
            $phpExecutable,
            'bin/console',
            'app:match-chat-messages',
            $signal->getCode(),
            $save ? '--save' : null,
            $verbosity
        ];
        $process = new Process($command);
        $process->setTimeout(300);
        $processor->run($io, $process, 'An error occurred while matching the chat messages.', null, OutputInterface::VERBOSITY_NORMAL);
        $io->newLine();

        if ($save) {
            $io->success('Finished processing the bat signal.');
        }
        else {
            $io->note('Finished processing the bat signal but any results have not been saved. Pass the `--save` option to save the process results in the database.');
        }

        return 0;
    }
}
