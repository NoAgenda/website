<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeChapterDraftRepository")
 * @ORM\Table(name="na_episode_chapter_draft")
 */
class EpisodeChapterDraft
{
    use CreatorTrait;
    use EpisodeChapterTrait;
    use FeedbackItemTrait;

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
     * @ORM\ManyToOne(targetEntity="Episode")
     * @ORM\JoinColumn(nullable=false)
     */
    private $episode;

    /**
     * @var EpisodeChapter|null
     *
     * @ORM\ManyToOne(targetEntity="EpisodeChapter", inversedBy="drafts")
     * @ORM\JoinColumn(nullable=true)
     */
    private $chapter;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isDraft(): bool
    {
        return true;
    }
}
