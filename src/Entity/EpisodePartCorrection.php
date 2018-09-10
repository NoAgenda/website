<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodePartCorrectionRepository")
 */
class EpisodePartCorrection
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
     * @var EpisodePart
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\EpisodePart", inversedBy="corrections")
     * @ORM\JoinColumn(nullable=false)
     */
    private $part;

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
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $startsAt;

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPart(): EpisodePart
    {
        return $this->part;
    }

    public function setPart(EpisodePart $part): self
    {
        $this->part = $part;

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

    public function getStartsAt(): ?int
    {
        return $this->startsAt;
    }

    public function setStartsAt(?int $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }
}
