<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearUsersCommand extends Command
{
    protected static $defaultName = 'clear-users';
    protected static $defaultDescription = 'Clears users that don\'t have any persisted data in the database';

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findInactiveUsers();
        $lastUserKey = array_key_last($users);

        foreach ($users as $key => $user) {
            $output->writeln(sprintf('Removing user: %s (%s)', $user->getId(), $user->getUsername()));

            $this->userRepository->remove($user, $key === $lastUserKey || $key % 100 === 0);
        }

        $style->success(sprintf('Cleared %s users', count($users)));

        return Command::SUCCESS;
    }
}
