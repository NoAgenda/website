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
    public function findNewSuggestionsByEpisode(Episode $episode, bool $unreviewed = false): array
    {
        $builder = $this->createQueryBuilder('draft');

        $whereClauses = [
            $builder->expr()->eq('draft.episode', ':episode'),
            $builder->expr()->eq('feedbackItem.accepted', 0),
            $builder->expr()->eq('feedbackItem.rejected', 0),
            $builder->expr()->eq('creator.banned', 0),
            $builder->expr()->eq('creator.hidden', 0),
        ];

        if (!$unreviewed) {
            $whereClauses[] = $builder->expr()->eq('creator.reviewed', 1);
        }

        return $builder
            ->leftJoin('draft.feedbackItem', 'feedbackItem')
            ->leftJoin('draft.creator', 'creator')
            ->andWhere($builder->expr()->andX(...$whereClauses))
            ->setParameter('episode', $episode->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * @return EpisodeChapterDraft[]
     */
    public function findAcceptedDraftsByChapter(EpisodeChapter $chapter): array
    {
        $builder = $this->createQueryBuilder('draft');

        return $builder
            ->leftJoin('draft.feedbackItem', 'feedbackItem')
            ->andWhere($builder->expr()->andX(
                $builder->expr()->eq('draft.chapter', ':chapter'),
                $builder->expr()->eq('feedbackItem.accepted', ':handled')
            ))
            ->setParameter('chapter', $chapter->getId())
            ->setParameter('handled', '1')
            ->getQuery()
            ->getResult();
    }
}
