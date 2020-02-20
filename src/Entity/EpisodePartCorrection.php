<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodePartCorrectionRepository")
 * @ORM\Table(name="na_episode_part_correction")
 */
class EpisodePartCorrection
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
     * @var EpisodePart
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\EpisodePart", inversedBy="corrections")
     * @ORM\JoinColumn(nullable=false)
     */
    private $part;

    /**
     * @var EpisodePart|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\EpisodePart")
     */
    private $result;

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

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\EpisodePartCorrectionVote", mappedBy="correction", orphanRemoval=true)
     */
    private $votes;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $action;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $position;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=1023, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $handled = false;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $startsAt;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->getSummary();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPart(): ?EpisodePart
    {
        return $this->part;
    }

    public function setPart(EpisodePart $part): self
    {
        $this->part = $part;

        return $this;
    }

    public function getResult(): ?EpisodePart
    {
        return $this->result;
    }

    public function setResult(?EpisodePart $result): self
    {
        $this->result = $result;

        return $this;
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

    public function getCreatorToken(): ?UserToken
    {
        return $this->creatorToken;
    }

    public function setCreatorToken(?UserToken $creatorToken): self
    {
        $this->creatorToken = $creatorToken;

        return $this;
    }

    /**
     * @return Collection|EpisodePartCorrectionVote[]
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(EpisodePartCorrectionVote $vote): self
    {
        if (!$this->votes->contains($vote)) {
            $this->votes[] = $vote;
            $vote->setCorrection($this);
        }

        return $this;
    }

    public function removeVote(EpisodePartCorrectionVote $vote): self
    {
        if ($this->votes->contains($vote)) {
            $this->votes->removeElement($vote);
            // set the owning side to null (unless already changed)
            if ($vote->getCorrection() === $this) {
                $vote->setCorrection(null);
            }
        }

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isHandled(): bool
    {
        return $this->handled;
    }

    public function setHandled(bool $handled): self
    {
        $this->handled = $handled;

        return $this;
    }

    public function getStartsAt(): ?int
    {
        return $this->startsAt;
    }

    public function setStartsAt(?int $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSummary(): string
    {
        if ($this->position !== null) {
            $summary = 'New chapter ' . $this->position . ' this';

            if ($this->startsAt !== null) {
                $summary .= ' at ' . $this->prettyTimestamp($this->startsAt);
            }

            if ($this->name !== null) {
                $summary .= ': ' . $this->name;
            }

            return $summary;
        }

        if ($this->action !== null) {
            $actions = [
                'remove' => 'This chapter wasn\'t played here',
                'name' => sprintf('The chapter name should be: %s', $this->name),
                'startsAt' => sprintf('The chapter starting time should be: %s', $this->prettyTimestamp($this->startsAt)),
            ];

            return $actions[$this->action];
        }

        return 'Empty correction';
    }

    private function prettyTimestamp($value): string
    {
        $value = (int) $value;

        $hours = floor($value / 60 / 60);
        $value = $value - ($hours * 60 * 60);

        $minutes = floor($value / 60);
        $value = $value - ($minutes * 60);

        $seconds = (string) $value;
        $seconds = strlen($seconds) === 1 ? '0' . $seconds : $seconds;

        if ($hours == 0) {
            return implode(':', [$minutes, $seconds]);
        }

        $minutes = strlen($minutes) == 1 ? '0' . $minutes : $minutes;

        return implode(':', [$hours, $minutes, $seconds]);
    }
}
