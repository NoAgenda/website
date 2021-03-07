<?php

namespace App\Command;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

class RedundancyCheckCommand extends Command
{
    protected static $defaultName = 'app:redundancy-check';

    private $entityManager;
    private $notifier;

    public function __construct(string $name = null, EntityManagerInterface $entityManager, NotifierInterface $notifier)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->notifier = $notifier;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Redundancy checks for crawling jobs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $latestEpisode = $this->entityManager->getRepository(Episode::class)->findLatest();

        if ($latestEpisode->getPublishedAt() < new \DateTime('today')) {
            $io->warning('No new episodes found.');

            $notification = new Notification('No new episodes found.', ['chat/slack_default']);
            $this->notifier->send($notification);
        }

        return Command::SUCCESS;
    }
}
