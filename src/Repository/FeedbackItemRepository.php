<?php

namespace App\Repository;

use App\Entity\FeedbackItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeedbackItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackItem[]    findAll()
 * @method FeedbackItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method FeedbackItem|null findOneBy(array $criteria, array $orderBy = null)
 */
class FeedbackItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackItem::class);
    }

    /**
     * @deprecated
     * @return FeedbackItem[]
     */
    public function findOpenFeedbackItems($limit): array
    {
        return $this->findUnresolvedItems($limit);
    }

    /**
     * @return FeedbackItem[]
     */
    public function findPublicUnresolvedItems(int $limit, bool $unreviewed = false): array
    {
        $builder = $this->createQueryBuilder('item');

        $whereClauses = [
            $builder->expr()->eq('item.accepted', 0),
            $builder->expr()->eq('item.rejected', 0),
            $builder->expr()->eq('creator.banned', 0),
            $builder->expr()->eq('creator.hidden', 0),
        ];

        if (!$unreviewed) {
            $whereClauses[] = $builder->expr()->eq('creator.reviewed', 1);
        }

        $items = $builder
            ->leftJoin('item.creator', 'creator')
            ->andWhere($builder->expr()->andX(...$whereClauses))
            ->orderBy('item.createdAt', 'desc')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($items as $item) {
            $this->resolveFeedbackEntity($item);
        }

        return $items;
    }

    /**
     * @return FeedbackItem[]
     */
    public function findUnresolvedItems($limit): array
    {
        $unresolvedItems = $this->findBy(['accepted' => 0, 'rejected' => 0], ['createdAt' => 'desc'], $limit);

        foreach ($unresolvedItems as $item) {
            $this->resolveFeedbackEntity($item);
        }

        return $unresolvedItems;
    }

    public function findByCreator(User $creator): array
    {
        $items = $this->findBy(['creator' => $creator], ['createdAt' => 'desc']);

        foreach ($items as $item) {
            $this->resolveFeedbackEntity($item);
        }

        return $items;
    }

    public function countUnresolvedItems(): int
    {
        return $this->count(['accepted' => 0, 'rejected' => 0]);
    }

    private function resolveFeedbackEntity(FeedbackItem $item): void
    {
        $item->setEntity($this->getEntityManager()->find($item->getEntityName(), $item->getEntityId()));
    }
}
