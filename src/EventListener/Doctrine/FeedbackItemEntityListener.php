<?php

namespace App\EventListener\Doctrine;

use App\Entity\FeedbackItem;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\RouterInterface;

class FeedbackItemEntityListener implements EventSubscriber
{
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly RouterInterface $router,
    ) {}

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
            $this->checkReviewStatus($item);

            $event->getEntityManager()->persist($item->getCreator());
        }
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $item = $event->getEntity();

        if ($item instanceof FeedbackItem) {
            $this->checkReviewStatus($item);

            $event->getEntityManager()->persist($item->getCreator());
        }
    }

    private function checkReviewStatus(FeedbackItem $item): void
    {
        if (!$item->getCreator()->isReviewed() && !$item->getCreator()->getNeedsReview()) {
            $item->getCreator()->setNeedsReview(true);

            $reviewUrl = $this->router->generate('feedback_user', ['user' => $item->getCreator()->getId()], RouterInterface::ABSOLUTE_URL);
            $this->notifier->send(new Notification("A new user is waiting for review.\n\n$reviewUrl"));
        }
    }
}
