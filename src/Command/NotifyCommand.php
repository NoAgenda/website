<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\RouterInterface;

class NotifyCommand extends Command
{
    protected static $defaultName = 'notify';

    private NotifierInterface $notifier;
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    public function __construct(string $name = null, NotifierInterface $notifier, RouterInterface $router)
    {
        parent::__construct($name);

        $this->notifier = $notifier;
        $this->router = $router;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
//        $output->writeln($this->router->generate('player', ['episode' => '200'], RouterInterface::ABSOLUTE_URL));
//
//        return Command::SUCCESS;

        $notification = (new Notification('Subject', ['chat/slack_feedback']))
            ->content('Content')
        ;

        $this->notifier->send($notification);

        return Command::SUCCESS;
    }
}
