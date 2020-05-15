<?php

namespace App\Repository;

use App\Entity\FeedbackVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeedbackVote|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackVote|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackVote[]    findAll()
 * @method FeedbackVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackVote::class);
    }
}
