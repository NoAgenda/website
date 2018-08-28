<?php

namespace App\Command;

use App\BatSignalReceiver;
use App\Entity\BatSignal;
use App\Repository\BatSignalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrawlBatSignalCommand extends Command
{
    protected static $defaultName = 'app:crawl-bat-signal';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var BatSignalRepository
     */
    private $signalRepository;

    public function __construct(?string $name = null, EntityManagerInterface $entityManager, BatSignalRepository $signalRepository)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->signalRepository = $signalRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawls the latest bat signal')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save crawling results in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $save = $input->getOption('save');

        $io->title('No Agenda Bat Signal Crawler');

        try {
            $data = (new BatSignalReceiver)->receive();
        }
        catch (\RuntimeException $exception) {
            $io->error($exception->getMessage());

            return 1;
        }

        $code = $data['code'];
        /** @var \DateTimeInterface $deployedAt */
        $deployedAt = $data['deployedAt'];

        $io->text(sprintf('Found a new bat signal with code "%s" was deployed at %s.', $code, $deployedAt->format('Y-m-d H:i:s')));

        $signal = $this->signalRepository->findOneBy([
            'code' => $code,
            'deployedAt' => $deployedAt,
        ]);

        if ($signal !== null) {
            $io->note('The latest bat signal has already been crawled.');

            return 0;
        }

        $signal = (new BatSignal)
            ->setCode($code)
            ->setProcessed(false)
            ->setDeployedAt($deployedAt)
        ;

        $this->entityManager->persist($signal);

        if ($save) {
            $this->entityManager->flush();

            $io->success('Saved the new bat signal.');
        }
        else {
            $io->note('The crawling results have not been saved. Pass the `--save` option to save the results in the database.');
        }

        return 0;
    }
}
