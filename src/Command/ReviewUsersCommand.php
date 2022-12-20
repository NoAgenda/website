<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'review',
)]
class ReviewUsersCommand extends Command
{
    public function __construct(private readonly UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->userRepository->findUnreviewedUsers() as $user) {
            $user->setReviewed(true);

            $this->userRepository->persist($user);
        }

        $this->userRepository->flush();

        return Command::SUCCESS;
    }
}
