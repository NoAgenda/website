<?php

namespace App\Repository;

use App\Entity\EpisodePartCorrection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method EpisodePartCorrection|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodePartCorrection|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodePartCorrection[]    findAll()
 * @method EpisodePartCorrection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodePartCorrectionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EpisodePartCorrection::class);
    }
}
