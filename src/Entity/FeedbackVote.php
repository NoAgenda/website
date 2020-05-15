<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FeedbackVoteRepository")
 * @ORM\Table(name="na_feedback_vote")
 */
class FeedbackVote
{
    use CreatorTrait;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var FeedbackItem
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\FeedbackItem", inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $item;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $supported;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $rejected;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
