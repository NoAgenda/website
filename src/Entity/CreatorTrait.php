<?php

namespace App\Entity;

/**
 * @property \DateTimeImmutable $createdAt
 * @property User $creator
 */
trait CreatorTrait
{
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }
}
