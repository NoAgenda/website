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

    public function isAccepted(): bool
    {
        return $this->feedbackItem->isAccepted();
    }

    public function setAccepted(bool $accepted): self
    {
        $this->feedbackItem->setAccepted($accepted);

        return $this;
    }

    public function isRejected(): bool
    {
        return $this->feedbackItem->isRejected();
    }

    public function setRejected(bool $rejected): self
    {
        $this->feedbackItem->setRejected($rejected);

        return $this;
    }
}
