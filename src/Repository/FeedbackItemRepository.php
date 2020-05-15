<?php

namespace App\Repository;

use App\Entity\FeedbackItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeedbackItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackItem[]    findAll()
 * @method FeedbackItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackItem::class);
    }

    /**
     * @return FeedbackItem[]
     */
    public function findOpenFeedbackItems($limit)
    {
        $openFeedbackItems = $this->findBy(['accepted' => 0, 'rejected' => 0], ['createdAt' => 'desc'], $limit);

        foreach ($openFeedbackItems as $feedbackItem) {
            $feedbackItem->setEntity($this->getEntityManager()->find($feedbackItem->getEntityName(), $feedbackItem->getEntityId()));
        }

        return $openFeedbackItems;
    }
}
