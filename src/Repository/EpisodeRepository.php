<?php

namespace App\Repository;

use App\Entity\Episode;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Pagerfanta;

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

    /** @return Episode[] */
    public function findEpisodesSince(\DateTimeInterface $date): array
    {
        $builder = $this->createQueryBuilder('episode');

        $query = $builder
            ->where($builder->expr()->gte('episode.publishedAt', ':date'))
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
        ;

        $episodes = [];

        foreach ($query->getResult() as $episode) {
            $episodes[$episode->getCode()] = $episode;
        }

        return $episodes;
    }

    public function findLastPublishedEpisode(): Episode
    {
        return $this->findOneBy([
            'published' => true,
        ]);
    }

    /** @return Episode[] */
    public function findLatestEpisodes(int $count = 4, bool $published = true): array
    {
        $publishedCriteria = [
            'published' => true,
        ];

        return $this->findBy($published ? $publishedCriteria : null, null, $count);
    }

    public function findPublishedEpisodes(): array
    {
        return $this->findBy([
            'published' => true,
        ]);
    }

    /** @return Episode[] */
    public function getHomepageEpisodes(): array
    {
        return $this->findLatestEpisodes();
    }

    public function paginateEpisodes($page = 1): Pagerfanta
    {
        $builder = $this->createQueryBuilder('episode');

        $builder
            ->select('episode', 'chapter')
            ->where($builder->expr()->eq('episode.published', true))
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
            ->where($builder->expr()->andX([
                $builder->expr()->eq('episode.published', true),
                $builder->expr()->eq('episode.special', true),
            ]))
            ->leftJoin('episode.chapters', 'chapter')
            ->orderBy('episode.publishedAt', 'desc')
        ;

        return $this->createPaginator($builder->getQuery(), $page);
    }
}
