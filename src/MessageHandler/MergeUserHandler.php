<?php

namespace App\MessageHandler;

use App\Message\MergeUser;
use App\Repository\UserRepository;
use App\Security\UserMerger;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MergeUserHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserMerger $userMerger,
    ) {}

    public function __invoke(MergeUser $message): void
    {
        $user = $this->userRepository->find($message->id);

        $this->userMerger->merge($user);
    }
}
