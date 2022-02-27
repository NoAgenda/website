<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;

trait EpisodeChapterTrait
{
    #[Column(type: 'string', length: 1024, nullable: true)]
    private ?string $name;

    #[Column(type: 'text', nullable: true)]
    private ?string $description;

    #[Column(type: 'integer')]
    private ?int $startsAt;

    #[Column(type: 'integer', nullable: true)]
    private ?int $duration;

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
