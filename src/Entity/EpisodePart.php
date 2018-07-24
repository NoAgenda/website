<?php

namespace App\Entity;

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

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEpisode(): Episode
    {
        return $this->episode;
    }

    public function setEpisode(Episode $episode): self
    {
        $this->episode = $episode;

        return $this;
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function setCreator(User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getName(): string
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

    public function getStartsAt(): int
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
}
