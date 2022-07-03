<?php

namespace App\Entity;

use App\Repository\FeedbackVoteRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: FeedbackVoteRepository::class)]
#[Table(name: 'na_feedback_vote')]
class FeedbackVote implements UserCreatedInterface
{
    use CreatorTrait;

    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id = null;

    #[ManyToOne(targetEntity: FeedbackItem::class, inversedBy: 'votes')]
    #[JoinColumn(nullable: false)]
    private ?FeedbackItem $item = null;

    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[Column(type: 'boolean')]
    private bool $supported = false;

    #[Column(type: 'boolean')]
    private bool $rejected = false;

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): ?FeedbackItem
    {
        return $this->item;
    }

    public function setItem(?FeedbackItem $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getSupported(): ?bool
    {
        return $this->supported;
    }

    public function setSupported(): self
    {
        $this->supported = true;
        $this->rejected = false;

        return $this;
    }

    public function getRejected(): ?bool
    {
        return $this->rejected;
    }

    public function setRejected(): self
    {
        $this->supported = false;
        $this->rejected = true;

        return $this;
    }
}
