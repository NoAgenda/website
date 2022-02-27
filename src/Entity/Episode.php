<?php

namespace App\Entity;

use App\Crawling\Shownotes\ShownotesParserFactory;
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

    #[Column(type: 'boolean')]
    private bool $cover = false;

    #[Column(type: 'boolean')]
    private bool $special = false;

    #[Column(type: 'boolean')]
    private bool $chatMessages = false;

    #[Column(type: 'boolean')]
    private bool $transcript = false;

    #[Column(type: 'string', length: 16, nullable: true)]
    private ?string $transcriptType;

    #[Column(type: 'date')]
    private ?\DateTimeInterface $publishedAt;

    #[Column(type: 'text', nullable: true)]
    private ?string $coverUri;

    #[Column(type: 'text')]
    private ?string $recordingUri;

    #[Column(type: 'text', nullable: true)]
    private ?string $shownotesUri;

    #[Column(type: 'text', nullable: true)]
    private ?string $transcriptUri;

    #[Column(type: 'text', nullable: true)]
    private ?string $chatNotice;

    #[Column(type: 'integer', nullable: true)]
    private ?int $duration;

    #[Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $recordedAt;

    #[Column(type: 'array', nullable: true)]
    private ?array $crawlerOutput;

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

    public function hasCover(): bool
    {
        return $this->cover;
    }

    public function setCover(bool $cover): self
    {
        $this->cover = $cover;

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

    public function getTranscriptType(): ?string
    {
        return $this->transcriptType;
    }

    public function setTranscriptType(?string $transcriptType): self
    {
        $this->transcriptType = $transcriptType;

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

    public function setCoverUri(?string $uri): self
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

    public function hasShownotes(): bool
    {
        return file_exists(ShownotesParserFactory::getShownotesPath($this));
    }

    public function getChatMessagesPath(): string
    {
        return sprintf('%s/chat_messages/%s.json', $_SERVER['APP_STORAGE_PATH'], $this->code);
    }

    public function getChatMessagesExist(): bool
    {
        return file_exists($this->getChatMessagesPath());
    }

    public function getTranscriptPath(): ?string
    {
        if (!$this->getTranscriptType()) {
            return null;
        }

        return sprintf('%s/episode_transcripts/%s.%s', $_SERVER['APP_STORAGE_PATH'], $this->getCode(), $this->getTranscriptType());
    }

    public function getTranscriptExists(): bool
    {
        return file_exists($this->getTranscriptPath());
    }
}
