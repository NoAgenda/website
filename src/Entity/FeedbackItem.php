<?php

namespace App\Entity;

use App\Repository\FeedbackItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: FeedbackItemRepository::class)]
#[Table(name: 'na_feedback_item')]
class FeedbackItem
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id;

    #[OneToMany(mappedBy: 'item', targetEntity: FeedbackVote::class)]
    private $votes;

    #[Column(type: 'string', length: 255)]
    private ?string $entityName;

    #[Column(type: 'integer', nullable: true)]
    private ?int $entityId;

    #[Column(type: 'boolean')]
    private bool $accepted = false;

    #[Column(type: 'boolean')]
    private bool $rejected = false;

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private ?object $entity = null;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|FeedbackVote[]
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(FeedbackVote $vote): self
    {
        if (!$this->votes->contains($vote)) {
            $this->votes[] = $vote;

            $vote->setItem($this);
        }

        return $this;
    }

    public function removeVote(FeedbackVote $vote): self
    {
        if ($this->votes->contains($vote)) {
            $this->votes->removeElement($vote);

            // set the owning side to null (unless already changed)
            if ($vote->getItem() === $this) {
                $vote->setItem(null);
            }
        }

        return $this;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): self
    {
        $this->entityName = $entityName;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getAccepted(): bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted): self
    {
        $this->accepted = $accepted;

        return $this;
    }

    public function getRejected(): bool
    {
        return $this->rejected;
    }

    public function setRejected(bool $rejected): self
    {
        $this->rejected = $rejected;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    public function setEntity(?object $entity): self
    {
        $this->entity = $entity;

        return $this;
    }
}
