<?php

namespace App\Entity;

use App\Repository\EpisodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: EpisodeRepository::class)]
#[Table(name: 'na_episode')]
class Episode
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id;

    #[OneToMany(mappedBy: 'episode', targetEntity: EpisodeChapter::class)]
    private Collection $chapters;

    #[Column(type: 'string', length: 16)]
    private ?string $code;

    #[Column(type: 'string', length: 255)]
    private ?string $name;

    #[Column(type: 'string', length: 255)]
    private ?string $author;

    #[Column(type: 'date')]
    private ?\DateTimeInterface $publishedAt;

    #[Column(type: 'boolean')]
    private bool $published = false;

    #[Column(type: 'boolean')]
    private bool $special = false;

    #[Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    #[Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $recordedAt = null;

    #[Column(type: 'text')]
    private ?string $recordingUri;

    #[Column(type: 'text', nullable: true)]
    private ?string $coverUri = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $coverPath = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $publicShownotesUri = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $shownotesUri = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $shownotesPath = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $transcriptUri = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $transcriptPath = null;

    #[Column(type: 'string', length: 16, nullable: true)]
    private ?string $transcriptType = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $chatArchivePath = null;

    #[Column(type: 'text', nullable: true)]
    private ?string $chatNotice = null;

    #[Column(type: 'array', nullable: true)]
    private ?array $crawlerOutput = null;

    #[Column(type: 'json', nullable: true)]
    private ?array $recordingTimeMatrix = null;

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

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeInterface $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

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

    public function getRecordingUri(): ?string
    {
        return $this->recordingUri;
    }

    public function setRecordingUri(string $uri): self
    {
        $this->recordingUri = $uri;

        return $this;
    }

    public function getCoverUri(): ?string
    {
        return $this->coverUri;
    }

    public function setCoverUri(?string $uri): self
    {
        $this->coverUri = $uri;

        return $this;
    }

    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }

    public function setCoverPath(?string $path): self
    {
        $this->coverPath = $path;

        return $this;
    }

    public function hasCover(): bool
    {
        return $this->coverPath && file_exists($this->coverPath);
    }

    public function getPublicShownotesUri(): ?string
    {
        return $this->publicShownotesUri;
    }

    public function setPublicShownotesUri(?string $uri): self
    {
        $this->publicShownotesUri = $uri;

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

    public function getShownotesPath(): ?string
    {
        return $this->shownotesPath;
    }

    public function setShownotesPath(?string $path): self
    {
        $this->shownotesPath = $path;

        return $this;
    }

    public function hasShownotes(): bool
    {
        return $this->shownotesPath && file_exists($this->shownotesPath);
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

    public function getTranscriptPath(): ?string
    {
        return $this->transcriptPath;
    }

    public function setTranscriptPath(?string $path): self
    {
        $this->transcriptPath = $path;

        return $this;
    }

    public function hasTranscript(): bool
    {
        return $this->transcriptPath && file_exists($this->transcriptPath);
    }

    public function getTranscriptType(): ?string
    {
        return $this->transcriptType;
    }

    public function setTranscriptType(?string $transcriptType): self
    {
        $this->transcriptType = $transcriptType;

        return $this;
    }

    public function getChatArchivePath(): ?string
    {
        return $this->chatArchivePath;
    }

    public function setChatArchivePath(?string $path): self
    {
        $this->chatArchivePath = $path;

        return $this;
    }

    public function hasChatArchive(): bool
    {
        return $this->chatArchivePath && file_exists($this->chatArchivePath);
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

    public function getCrawlerOutput(): ?array
    {
        return $this->crawlerOutput;
    }

    public function setCrawlerOutput(?array $crawlerOutput): self
    {
        $this->crawlerOutput = $crawlerOutput;

        return $this;
    }

    public function getRecordingTimeMatrix(): ?array
    {
        return $this->recordingTimeMatrix;
    }

    public function setRecordingTimeMatrix(?array $recordingTimeMatrix): self
    {
        $this->recordingTimeMatrix = $recordingTimeMatrix;

        return $this;
    }
}
