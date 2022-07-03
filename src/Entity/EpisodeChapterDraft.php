<?php

namespace App\Entity;

use App\Repository\EpisodeChapterDraftRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: EpisodeChapterDraftRepository::class)]
#[Table(name: 'na_episode_chapter_draft')]
class EpisodeChapterDraft implements UserCreatedInterface
{
    use CreatorTrait;
    use EpisodeChapterTrait;
    use FeedbackItemTrait;

    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id = null;

    #[ManyToOne(targetEntity: Episode::class, inversedBy: 'chapters')]
    #[JoinColumn(nullable: false)]
    private ?Episode $episode = null;

    #[ManyToOne(targetEntity: EpisodeChapter::class, inversedBy: 'drafts')]
    private ?EpisodeChapter $chapter = null;

    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getChapter(): ?EpisodeChapter
    {
        return $this->chapter;
    }

    public function setChapter(?EpisodeChapter $chapter): self
    {
        $this->chapter = $chapter;

        return $this;
    }

    public function isDraft(): bool
    {
        return true;
    }
}
