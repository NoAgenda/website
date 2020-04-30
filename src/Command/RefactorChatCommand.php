<?php

namespace App\Command;

use App\Entity\ChatMessage;
use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefactorChatCommand extends Command
{
    protected static $defaultName = 'app:refactor-chat';

    private $entityManager;

    public function __construct(string $name = null, EntityManagerInterface $entityManager)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $episodes = $this->entityManager->getRepository(Episode::class)->findBy(['chatMessages' => true]);

        foreach ($episodes as $episode) {
            $messages = $this->entityManager->getRepository(ChatMessage::class)->findBy(['episode' => $episode->getId()]);

            $data = array_map(
                function (ChatMessage $message) {
                    $messageText = preg_replace('/[[:cntrl:]]/', '', $message->getContents());

                    return [
                        'username' => $message->getUsername(),
                        'contents' => nl2br($messageText),
                        'timestamp' => $message->getPostedAt(),
                    ];
                },
                $messages
            );

            $chatMessagesPath = sprintf('%s/chat_messages/%s.json', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

            file_put_contents($chatMessagesPath, json_encode($data));
        }

        return 0;
    }
}
