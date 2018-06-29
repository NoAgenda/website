<?php

namespace App\Repository;

use App\Entity\EpisodePart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method EpisodePart|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodePart|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodePart[]    findAll()
 * @method EpisodePart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodePartRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EpisodePart::class);
    }
}
