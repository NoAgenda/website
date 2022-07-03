<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Security\UserMerger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MergeUserCommand extends Command
{
    protected static $defaultName = 'merge-user';
    protected static $defaultDescription = 'Merge a user';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserMerger $userMerger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition([
            new InputArgument('user', InputArgument::REQUIRED, 'The user id to merge'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $user = $this->userRepository->find($id = $input->getArgument('user'));

        if (!$user) {
            $style->warning(sprintf('Invalid user id: %s', $id));

            return Command::INVALID;
        }

        if (!$user->getMaster()) {
            $style->warning('User is not marked for merging.');

            return Command::INVALID;
        }

        $this->userMerger->merge($user);

        return Command::SUCCESS;
    }
}
