<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: VideoRepository::class)]
#[Table(name: 'na_video')]
class Video
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id = null;

    #[Column(type: 'string', length: 255)]
    private ?string $title;

    #[Column(type: 'datetime')]
    private ?\DateTimeInterface $publishedAt;

    #[Column(type: 'string', length: 32)]
    private string $youtubeId;

    #[Column(type: 'string', length: 255, nullable: true)]
    private ?string $youtubeEtag = null;

    public function __construct(string $youtubeId)
    {
        $this->youtubeId = $youtubeId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeInterface $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getYoutubeId(): string
    {
        return $this->youtubeId;
    }

    public function setYoutubeId(string $youtubeId): self
    {
        $this->youtubeId = $youtubeId;

        return $this;
    }

    public function getYoutubeEtag(): ?string
    {
        return $this->youtubeEtag;
    }

    public function setYoutubeEtag(?string $youtubeEtag): self
    {
        $this->youtubeEtag = $youtubeEtag;

        return $this;
    }
}
