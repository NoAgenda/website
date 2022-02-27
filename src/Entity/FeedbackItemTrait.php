<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

trait FeedbackItemTrait
{
    #[OneToOne(targetEntity: FeedbackItem::class, cascade: ['all'])]
    #[JoinColumn(nullable: false)]
    private ?FeedbackItem $feedbackItem;

    public function getFeedbackItem(): ?FeedbackItem
    {
        return $this->feedbackItem;
    }

    public function setFeedbackItem(?FeedbackItem $feedbackItem): self
    {
        $this->feedbackItem = $feedbackItem;

        return $this;
    }

    public function getAccepted(): bool
    {
        return $this->feedbackItem->getAccepted();
    }

    public function setAccepted(bool $accepted): self
    {
        $this->feedbackItem->setAccepted($accepted);

        return $this;
    }

    public function getRejected(): bool
    {
        return $this->feedbackItem->getRejected();
    }

    public function setRejected(bool $rejected): self
    {
        $this->feedbackItem->setRejected($rejected);

        return $this;
    }
}
