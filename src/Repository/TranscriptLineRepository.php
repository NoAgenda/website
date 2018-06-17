<?php

namespace App\Repository;

use App\Entity\Episode;
use App\Entity\TranscriptLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method TranscriptLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method TranscriptLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method TranscriptLine[]    findAll()
 * @method TranscriptLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TranscriptLineRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TranscriptLine::class);
    }

    /**
     * @return TranscriptLine[]
     */
    public function findByEpisode(Episode $episode): array
    {
        return $this->findBy(['episode' => $episode], ['timestamp' => 'ASC']);
    }
}
