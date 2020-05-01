<?php

namespace App\Command;

use App\Entity\TranscriptLine;
use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefactorTranscriptsCommand extends Command
{
    protected static $defaultName = 'app:refactor-transcripts';

    private $entityManager;

    public function __construct(string $name = null, EntityManagerInterface $entityManager)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $episodes = $this->entityManager->getRepository(Episode::class)->findBy(['transcript' => true]);

        foreach ($episodes as $episode) {
            $lines = $this->entityManager->getRepository(TranscriptLine::class)->findBy(['episode' => $episode->getId()]);

            $data = array_map(
                function (TranscriptLine $line) {
                    return [
                        'timestamp' => $line->getTimestamp(),
                        'text' => $line->getText(),
                    ];
                },
                $lines
            );

            $transcriptPath = sprintf('%s/transcripts/%s.json', $_SERVER['APP_STORAGE_PATH'], $episode->getCode());

            file_put_contents($transcriptPath, json_encode($data));
        }

        return 0;
    }
}
