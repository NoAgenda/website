<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait CreatorTrait
{
    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true)
     */
    private $creator;

    /**
     * @var UserToken|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserToken")
     * @ORM\JoinColumn(nullable=true)
     */
    private $creatorToken;

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
