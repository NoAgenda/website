<?php

namespace App\Command;

use App\Crawling\NotificationPublisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupMastodonAnnouncementsCommand extends Command
{
    protected static $defaultName = 'mastodon:cleanup';
    protected static $defaultDescription = 'Retry deletion of tracked crawler Mastodon announcements';

    public function __construct(private readonly NotificationPublisher $notificationPublisher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->notificationPublisher->retryMastodonRetentionCleanup();

        return Command::SUCCESS;
    }
}
