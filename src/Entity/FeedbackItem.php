<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FeedbackItemRepository")
 * @ORM\Table(name="na_feedback_item")
 */
class FeedbackItem
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\FeedbackVote", mappedBy="item")
     */
    private $votes;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $entityName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $entityId;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $accepted = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $rejected = false;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @var object
     */
    private $entity;

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

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity): self
    {
        $this->entity = $entity;

        return $this;
    }
}
