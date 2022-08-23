<?php

namespace App\Repository;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EpisodeChapter|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodeChapter|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodeChapter[]    findAll()
 * @method EpisodeChapter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodeChapterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EpisodeChapter::class);
    }

    /**
     * @return EpisodeChapter[]
     */
    public function findByEpisode(Episode $episode): array
    {
        return $this->findBy(['episode' => $episode, 'deletedAt' => null], ['startsAt' => 'ASC']);
    }
}
