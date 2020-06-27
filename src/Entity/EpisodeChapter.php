<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeChapterRepository")
 * @ORM\Table(name="na_episode_chapter")
 */
class EpisodeChapter
{
    use CreatorTrait;
    use EpisodeChapterTrait;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Episode
     *
     * @ORM\ManyToOne(targetEntity="Episode", inversedBy="chapters")
     * @ORM\JoinColumn(nullable=false)
     */
    private $episode;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="EpisodeChapterDraft", mappedBy="chapter", cascade={"remove"})
     */
    private $drafts;

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

    public function setEpisode(?Episode $episode): self
    {
        $this->episode = $episode;

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
}
