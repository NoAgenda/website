<?php

namespace App\Entity;

use App\Repository\ScheduledFileDownloadRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[Entity(repositoryClass: ScheduledFileDownloadRepository::class)]
#[Table(name: 'na_file_download')]
#[UniqueEntity(['data', 'episode'])]
class ScheduledFileDownload
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id;

    #[ManyToOne(targetEntity: Episode::class)]
    #[JoinColumn(nullable: false)]
    private ?Episode $episode;

    #[Column(name: 'crawling_data', type: 'string', length: 32)]
    private ?string $data;

    #[Column(type: 'datetime')]
    private ?\DateTimeInterface $lastModifiedAt = null;

    #[Column(type: 'datetime')]
    private ?\DateTimeInterface $initializedAt;

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

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getLastModifiedAt(): ?\DateTimeInterface
    {
        return $this->lastModifiedAt;
    }

    public function setLastModifiedAt(\DateTimeInterface $lastModifiedAt): self
    {
        $this->lastModifiedAt = $lastModifiedAt;

        return $this;
    }

    public function getInitializedAt(): ?\DateTimeInterface
    {
        return $this->initializedAt;
    }

    public function setInitializedAt(\DateTimeInterface $initializedAt): self
    {
        $this->initializedAt = $initializedAt;

        return $this;
    }
}
