<?php

namespace App\Repository;

use App\Entity\EpisodePartCorrectionVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method EpisodePartCorrectionVote|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodePartCorrectionVote|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodePartCorrectionVote[]    findAll()
 * @method EpisodePartCorrectionVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodePartCorrectionVoteRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EpisodePartCorrectionVote::class);
    }
}
