<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeRepository")
 * @ORM\Table(name="na_episode")
 */
class Episode
{
    /**
     * @var integer
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

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
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $special;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $chatMessages;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $transcript;

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
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $chatNotice;

    /**
     * @var integer|null
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

    public function __toString(): string
    {
        return sprintf('%s: %s', $this->getCode(), $this->getName());
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAuthor(): string
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
        return $this->special ?? false;
    }

    public function setSpecial(bool $special): self
    {
        $this->special = $special;

        return $this;
    }

    public function hasChatMessages(): bool
    {
        return $this->chatMessages ?? false;
    }

    public function setChatMessages(bool $chatMessages): self
    {
        $this->chatMessages = $chatMessages;

        return $this;
    }

    public function hasTranscript(): bool
    {
        return $this->transcript ?? false;
    }

    public function setTranscript(bool $transcript): self
    {
        $this->transcript = $transcript;

        return $this;
    }

    public function getPublishedAt(): \DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeInterface $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCoverUri(): string
    {
        return $this->coverUri;
    }

    public function setCoverUri(string $uri): self
    {
        $this->coverUri = $uri;

        return $this;
    }

    public function getRecordingUri(): string
    {
        return $this->recordingUri;
    }

    public function setRecordingUri(string $uri): self
    {
        $this->recordingUri = $uri;

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

    public function getDuration(): int
    {
        return $this->duration ?? 0;
    }

    public function setDuration(int $duration): self
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

    public function getCrawlerOutput(): array
    {
        return $this->crawlerOutput ?? [];
    }

    public function setCrawlerOutput(array $crawlerOutput): self
    {
        $this->crawlerOutput = $crawlerOutput;

        return $this;
    }
}
