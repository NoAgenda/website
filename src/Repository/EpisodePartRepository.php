<?php

namespace App\Repository;

use App\Entity\EpisodePart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EpisodePart|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodePart|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodePart[]    findAll()
 * @method EpisodePart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodePartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EpisodePart::class);
    }
}
