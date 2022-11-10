<?php

namespace App\Command;

use App\Crawling\NotificationPublisher;
use App\Repository\EpisodeRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PublishNotificationsCommand extends Command
{
    protected static $defaultName = 'publish-notifications';
    protected static $defaultDescription = 'Publish push notifications for an episode';

    public function __construct(
        private readonly EpisodeRepository $episodeRepository,
        private readonly NotificationPublisher $notificationPublisher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition([
            new InputArgument('episode', InputArgument::REQUIRED, 'The episode code'),
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

        $this->notificationPublisher->sendUserEpisodeNotifications($episode);

        return Command::SUCCESS;
    }
}
