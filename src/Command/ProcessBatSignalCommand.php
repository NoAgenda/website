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

    /**
     * @var string
     */
    private $projectPath;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, EpisodeRepository $episodeRepository, BatSignalRepository $signalRepository, string $projectPath)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->episodeRepository = $episodeRepository;
        $this->signalRepository = $signalRepository;
        $this->projectPath = $projectPath;
    }

    protected function configure()
    {
        $this
            ->setDescription('Processes a bat signal')
            ->addArgument('episode', InputArgument::OPTIONAL, 'The episode code')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save processing results in the database')
            ->addOption('unprocessed', null, InputOption::VALUE_NONE, 'Find an unprocessed bat signal')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $code = $input->getArgument('episode');
        $unprocessed = $input->getOption('unprocessed');

        $io->title('No Agenda Bat Signal Processor');

        if ($code == null && $unprocessed) {
            $signal = $this->signalRepository->findOneUnprocessed();
        }
        elseif ($code) {
            $signal = $this->signalRepository->findOneBy(['code' => $code]);
        }
        else {
            $io->error('Unable to determine bat signal source. Pass the `--unprocessed` option to get started.');

            return 1;
        }

        if ($signal === null) {
            $io->error('No matching bat signal was found.');

            return $unprocessed ? 0 : 1;
        }

        if ($signal->getDeployedAt() > (new \DateTime)->modify('-6 hours')) {
            $io->error('The related bat signal is too fresh.');

            return 0;
        }

        $processor = $this->getHelper('process');
        $verbosity = $io->isDebug() ? '-vvv' : ($io->isVeryVerbose() ? '-vv' : ($io->isVerbose() ? '-v' : ''));
        $phpExecutable = (new ExecutableFinder)->find('php');

        // Match recording time
        $command = sprintf('%s bin/console app:match-recording-time %s %s %s', $phpExecutable, $signal->getCode(), $save ? '--save' : '', $verbosity);
        $process = new Process($command, $this->projectPath);
        $process->setTimeout(3000);
        $processor->run($io, $process, 'An error occurred while matching the recording time.', null, OutputInterface::VERBOSITY_NORMAL);
        $io->newLine();

        // Match chat messages
        $command = sprintf('%s bin/console app:match-chat-messages %s %s %s', $phpExecutable, $signal->getCode(), $save ? '--save' : '', $verbosity);
        $process = new Process($command, $this->projectPath);
        $process->setTimeout(300);
        $processor->run($io, $process, 'An error occurred while matching the chat messages.', null, OutputInterface::VERBOSITY_NORMAL);
        $io->newLine();

        if ($save) {
            $io->success('Finished processing the bat signal.');
        }
        else {
            $io->success('Finished processing the bat signal.');
            $io->note('Finished processing the bat signal but any results have not been saved. Pass the `--save` option to save the process results in the database.');
        }

        return 0;
    }
}
