<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ChatMessageRepository")
 */
class ChatMessage
{
    const SOURCE_WEBSITE = 1;
    const SOURCE_CHAT = 2;

    /**
     * @var integer
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
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $contents;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $postedAt;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $source;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEpisode(): Episode
    {
        return $this->episode;
    }

    public function setEpisode(Episode $episode): self
    {
        $this->episode = $episode;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function setContents(string $contents): self
    {
        $this->contents = $contents;

        return $this;
    }

    public function getPostedAt(): int
    {
        return $this->postedAt;
    }

    public function setPostedAt(int $postedAt): self
    {
        $this->postedAt = $postedAt;

        return $this;
    }

    public function getSource(): int
    {
        return $this->source;
    }

    public function fromChat(): self
    {
        $this->source = self::SOURCE_CHAT;

        return $this;
    }

    public function fromWebsite(): self
    {
        $this->source = self::SOURCE_WEBSITE;

        return $this;
    }

    public function setSource(int $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
