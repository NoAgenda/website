<?php

namespace App\Repository;

use App\Entity\Episode;
use App\Entity\ScheduledFileDownload;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ScheduledFileDownload|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScheduledFileDownload|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScheduledFileDownload[]    findAll()
 * @method ScheduledFileDownload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScheduledFileDownloadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledFileDownload::class);
    }

    public function findDownload(string $crawler, Episode $episode): ?ScheduledFileDownload
    {
        return $this->findOneBy([
            'crawler' => $crawler,
            'episode' => $episode,
        ]);
    }
}
