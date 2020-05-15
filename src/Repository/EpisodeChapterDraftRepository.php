<?php

namespace App\Repository;

use App\Entity\Episode;
use App\Entity\EpisodeChapter;
use App\Entity\EpisodeChapterDraft;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EpisodeChapterDraft|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodeChapterDraft|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodeChapterDraft[]    findAll()
 * @method EpisodeChapterDraft[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodeChapterDraftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EpisodeChapterDraft::class);
    }

    /**
     * @return EpisodeChapterDraft[]
     */
    public function findNewSuggestionsByEpisode(Episode $episode): array
    {
        $builder = $this->createQueryBuilder('draft');

        $builder
            ->leftJoin('draft.feedbackItem', 'feedbackItem')
            ->andWhere($builder->expr()->andX(
                $builder->expr()->eq('draft.episode', ':episode'),
                // $builder->expr()->isNull('draft.chapter'),
                $builder->expr()->eq('feedbackItem.accepted', ':unhandled'),
                $builder->expr()->eq('feedbackItem.rejected', ':unhandled')
            ))
            ->setParameter('episode', $episode->getId())
            ->setParameter('unhandled', '0')
        ;

        $query = $builder->getQuery();

        return $query->getResult();
    }

    /**
     * @return EpisodeChapterDraft[]
     */
    public function findAcceptedDraftsByChapter(EpisodeChapter $chapter): array
    {
        $builder = $this->createQueryBuilder('draft');

        $builder
            ->leftJoin('draft.feedbackItem', 'feedbackItem')
            ->andWhere($builder->expr()->andX(
                $builder->expr()->eq('draft.chapter', ':chapter'),
                $builder->expr()->eq('feedbackItem.accepted', ':handled')
            ))
            ->setParameter('chapter', $chapter->getId())
            ->setParameter('handled', '1')
        ;

        $query = $builder->getQuery();

        return $query->getResult();
    }
}
