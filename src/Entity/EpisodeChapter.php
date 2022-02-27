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
class EpisodeChapter
{
    use CreatorTrait;
    use EpisodeChapterTrait;

    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id;

    #[ManyToOne(targetEntity: Episode::class, inversedBy: 'chapters')]
    #[JoinColumn(nullable: false)]
    private ?Episode $episode;

    #[OneToMany(mappedBy: 'chapters', targetEntity: EpisodeChapterDraft::class)]
    private Collection $drafts;

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->drafts = new ArrayCollection();
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
        if ($this->drafts->contains($draft)) {
            $this->drafts->removeElement($draft);

            // set the owning side to null (unless already changed)
            if ($draft->getChapter() === $this) {
                $draft->setChapter(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isDraft(): bool
    {
        return false;
    }
}
