<?php

namespace App\Repository;

use App\Entity\Episode;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Pagerfanta;
use function Doctrine\ORM\QueryBuilder;

/**
 * @method Episode|null find($id, $lockMode = null, $lockVersion = null)
 * @method Episode|null findOneBy(array $criteria = null, array $orderBy = null)
 * @method Episode|null findOneByCode(string $code, array $orderBy = null)
 * @method Episode[]    findBy(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
 * @method Episode[]    findAll()
 */
class EpisodeRepository extends AbstractRepository
{
    protected $defaultOrderBy = [
        'publishedAt' => 'desc',
    ];
    protected $itemsPerPage = 16;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Episode::class);
    }

    public function findLatest(): Episode
    {
        return $this->findOneBy();
    }

    public function findFeedEpisodes(): array
    {
        $episodes = [];

        foreach ($this->findBy(null, null, 32) as $episode) {
            $episodes[$episode->getCode()] = $episode;
        }

        return $episodes;
    }

    public function paginateEpisodes($page = 1): Pagerfanta
    {
        $builder = $this->createQueryBuilder('episode');

        $builder
            ->select('episode', 'chapter')
            ->leftJoin('episode.chapters', 'chapter')
            ->orderBy('episode.publishedAt', 'desc')
        ;

        return $this->createPaginator($builder->getQuery(), $page);
    }

    public function paginateSpecialEpisodes($page = 1): Pagerfanta
    {
        $builder = $this->createQueryBuilder('episode');

        $builder
            ->select('episode', 'chapter')
            ->where($builder->expr()->eq('episode.special', true))
            ->leftJoin('episode.chapters', 'chapter')
            ->orderBy('episode.publishedAt', 'desc')
        ;

        return $this->createPaginator($builder->getQuery(), $page);
    }

    public function getHomepageEpisodes()
    {
        return $this->findBy(null, null, 4);
    }
}
