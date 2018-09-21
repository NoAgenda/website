<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodePartRepository")
 * @ORM\Table(name="na_episode_part")
 */
class EpisodePart
{
    /**
     * @var integer
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Episode
     *
     * @ORM\ManyToOne(targetEntity="Episode")
     * @ORM\JoinColumn(nullable=false)
     */
    private $episode;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $creator;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\EpisodePartCorrection", mappedBy="part")
     */
    private $corrections;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1023)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $startsAt;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    public function __construct()
    {
        $this->corrections = new ArrayCollection;
        $this->enabled = true;
        $this->createdAt = new \DateTimeImmutable;
    }

    public function __toString(): string
    {
        return sprintf('%s [%s]', $this->getName(), $this->getEpisode());
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEpisode(): ?Episode
    {
        return $this->episode;
    }

    public function setEpisode(Episode $episode): self
    {
        $this->episode = $episode;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @return Collection|EpisodePartCorrection[]
     */
    public function getCorrections(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('handled', false))
        ;

        return $this->corrections->matching($criteria);
    }

    public function addCorrection(EpisodePartCorrection $correction): self
    {
        if (!$this->corrections->contains($correction)) {
            $this->corrections[] = $correction;

            $correction->setPart($this);
        }

        return $this;
    }

    public function removeCorrection(EpisodePartCorrection $correction): self
    {
        if ($this->corrections->contains($correction)) {
            $this->corrections->removeElement($correction);

            // set the owning side to null (unless already changed)
            if ($correction->getPart() === $this) {
                $correction->setPart(null);
            }
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
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

    public function getStartsAt(): ?int
    {
        return $this->startsAt;
    }

    public function setStartsAt(int $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
