<?php

namespace App\Repository;

use App\Entity\EpisodePartCorrection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EpisodePartCorrection|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodePartCorrection|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodePartCorrection[]    findAll()
 * @method EpisodePartCorrection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodePartCorrectionRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EpisodePartCorrection::class);
    }
}
