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
#[UniqueEntity(['crawler', 'episode'])]
class ScheduledFileDownload
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id;

    #[ManyToOne(targetEntity: Episode::class)]
    #[JoinColumn(nullable: false)]
    private ?Episode $episode;

    #[Column(type: 'string', length: 255)]
    private ?string $crawler;

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

    public function getCrawler(): ?string
    {
        return $this->crawler;
    }

    public function setCrawler(string $crawler): self
    {
        $this->crawler = $crawler;

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
