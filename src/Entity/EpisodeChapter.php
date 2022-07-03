<?php

namespace App\Entity;

use App\Repository\EpisodeChapterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: EpisodeChapterRepository::class)]
#[Table(name: 'na_episode_chapter')]
class EpisodeChapter implements UserCreatedInterface
{
    use CreatorTrait;
    use EpisodeChapterTrait;

    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id = null;

    #[ManyToOne(targetEntity: Episode::class, inversedBy: 'chapters')]
    #[JoinColumn(nullable: false)]
    private ?Episode $episode = null;

    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[OneToMany(mappedBy: 'chapter', targetEntity: EpisodeChapterDraft::class)]
    private Collection $drafts;

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->drafts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s [%s]', $this->getName(), $this->getEpisode());
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

    /**
     * @return Collection|EpisodeChapterDraft[]
     */
    public function getDrafts(): Collection
    {
        return $this->drafts;
    }

    public function addDraft(EpisodeChapterDraft $draft): self
    {
        if (!$this->drafts->contains($draft)) {
            $this->drafts[] = $draft;
            $draft->setChapter($this);
        }

        return $this;
    }

    public function removeDraft(EpisodeChapterDraft $draft): self
    {
        if ($this->drafts->removeElement($draft)) {
            if ($draft->getChapter() === $this) {
                $draft->setChapter(null);
            }
        }

        return $this;
    }

    public function isDraft(): bool
    {
        return false;
    }
}
