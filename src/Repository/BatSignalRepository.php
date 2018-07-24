<?php

namespace App\Repository;

use App\Entity\BatSignal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method BatSignal|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatSignal|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatSignal[]    findAll()
 * @method BatSignal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatSignalRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BatSignal::class);
    }

    public function findOneByCode($code): ?BatSignal
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findOneUnprocessed(): ?BatSignal
    {
        return $this->findOneBy(['processed' => false]);
    }
}
