<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

trait CreatorTrait
{
    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(nullable: true)]
    private ?User $creator;

    #[ManyToOne(targetEntity: UserToken::class)]
    #[JoinColumn(nullable: true)]
    private ?UserToken $creatorToken;

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreatorToken(): ?UserToken
    {
        return $this->creatorToken;
    }

    public function setCreatorToken(?UserToken $creatorToken): self
    {
        $this->creatorToken = $creatorToken;

        return $this;
    }
}
