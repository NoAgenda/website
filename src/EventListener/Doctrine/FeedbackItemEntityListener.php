<?php

namespace App\EventListener\Doctrine;

use App\Entity\FeedbackItem;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;

class FeedbackItemEntityListener implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            'prePersist',
            'preUpdate',
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $item = $event->getEntity();

        if ($item instanceof FeedbackItem) {
            if (!$item->getCreator()->isReviewed() && !$item->getCreator()->getNeedsReview()) {
                $item->getCreator()->setNeedsReview(true);

                $event->getEntityManager()->persist($item->getCreator());
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $item = $event->getEntity();

        if ($item instanceof FeedbackItem) {
            if (!$item->getCreator()->isReviewed() && !$item->getCreator()->getNeedsReview()) {
                $item->getCreator()->setNeedsReview(true);

                $event->getEntityManager()->persist($item->getCreator());
            }
        }
    }
}
