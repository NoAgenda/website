<?php

namespace App\Repository;

use App\Entity\BatSignal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BatSignal|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatSignal|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatSignal[]    findAll()
 * @method BatSignal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatSignalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatSignal::class);
    }

    public function exists(BatSignal $signal): bool
    {
        $existing = $this->findOneBy([
            'code' => $signal->getCode(),
            'deployedAt' => $signal->getDeployedAt(),
        ]);

        return null !== $existing;
    }
}
