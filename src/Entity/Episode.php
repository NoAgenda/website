<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeRepository")
 * @ORM\Table(name="na_episode")
 */
class Episode
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="EpisodeChapter", mappedBy="episode")
     */
    private $chapters;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=15)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $author;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $special = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $chatMessages = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $transcript = false;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="date")
     */
    private $publishedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $coverUri;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $recordingUri;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $shownotesUri;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $transcriptUri;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $chatNotice;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $recordedAt;

    /**
     * @var array|null
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $crawlerOutput;

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('%s: %s', $this->getCode(), $this->getName());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|FeedbackVote[]
     */
    public function getChapters(): Collection
    {
        return $this->chapters;
    }

    public function addChapter(EpisodeChapter $chapter): self
    {
        if (!$this->chapters->contains($chapter)) {
            $this->chapters[] = $chapter;

            $chapter->setEpisode($this);
        }

        return $this;
    }

    public function removeChapter(EpisodeChapter $chapter): self
    {
        if ($this->chapters->contains($chapter)) {
            $this->chapters->removeElement($chapter);

            // set the owning side to null (unless already changed)
            if ($chapter->getEpisode() === $this) {
                $chapter->setEpisode(null);
            }
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function isSpecial(): bool
    {
        return $this->special;
    }

    public function setSpecial(bool $special): self
    {
        $this->special = $special;

        return $this;
    }

    public function hasChatMessages(): bool
    {
        return $this->chatMessages;
    }

    public function setChatMessages(bool $chatMessages): self
    {
        $this->chatMessages = $chatMessages;

        return $this;
    }

    public function hasTranscript(): bool
    {
        return $this->transcript;
    }

    public function setTranscript(bool $transcript): self
    {
        $this->transcript = $transcript;

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

    public function getCoverUri(): ?string
    {
        return $this->coverUri;
    }

    public function setCoverUri(string $uri): self
    {
        $this->coverUri = $uri;

        return $this;
    }

    public function getRecordingUri(): ?string
    {
        return $this->recordingUri;
    }

    public function setRecordingUri(string $uri): self
    {
        $this->recordingUri = $uri;

        return $this;
    }

    public function getShownotesUri(): ?string
    {
        return $this->shownotesUri;
    }

    public function setShownotesUri(?string $uri): self
    {
        $this->shownotesUri = $uri;

        return $this;
    }

    public function getTranscriptUri(): ?string
    {
        return $this->transcriptUri;
    }

    public function setTranscriptUri(?string $uri): self
    {
        $this->transcriptUri = $uri;

        return $this;
    }

    public function getChatNotice(): ?string
    {
        return $this->chatNotice;
    }

    public function setChatNotice(?string $notice): self
    {
        $this->chatNotice = $notice;

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

    public function getRecordedAt(): ?\DateTimeInterface
    {
        return $this->recordedAt;
    }

    public function setRecordedAt(?\DateTimeInterface $recordedAt): self
    {
        $this->recordedAt = $recordedAt;

        return $this;
    }

    public function getCrawlerOutput(): ?array
    {
        return $this->crawlerOutput;
    }

    public function setCrawlerOutput(?array $crawlerOutput): self
    {
        $this->crawlerOutput = $crawlerOutput;

        return $this;
    }
}
