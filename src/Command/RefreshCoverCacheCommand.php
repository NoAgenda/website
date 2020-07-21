<?php

namespace App\Command;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshCoverCacheCommand extends Command
{
    protected static $defaultName = 'app:refresh-cover-cache';

    private $entityManager;
    private $filterManager;
    private $filterService;

    public function __construct(string $name = null, EntityManagerInterface $entityManager, FilterManager $filterManager, FilterService $filterService)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->filterManager = $filterManager;
        $this->filterService = $filterService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Refreshes the public cache of episode covers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $episodes = $this->entityManager->getRepository(Episode::class)->findAll();

        foreach ($episodes as $episode) {
            if (!$episode->getCoverUri()) {
                continue;
            }

            $this->resolveCoverCache($episode->getCode());
        }

        return 0;
    }

    private function resolveCoverCache(string $code): void
    {
        $filters = array_keys($this->filterManager->getFilterConfiguration()->all());

        foreach ($filters as $filter) {
            $this->filterService->bustCache("${code}.png", $filter);
            $this->filterService->getUrlOfFilteredImage("${code}.png", $filter);
        }
    }
}
