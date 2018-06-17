<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ShowRepository")
 * @ORM\Table(name="show_table")
 */
class Show
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
     * @var string
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duration;

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
    private $imageUri;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $audioUri;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $crawlerOutput;

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

    public function getDuration(): int
    {
        return $this->duration ?? 0;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

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

    public function getImageUri(): string
    {
        return $this->imageUri;
    }

    public function setImageUri(string $imageUri): self
    {
        $this->imageUri = $imageUri;

        return $this;
    }

    public function getAudioUri(): string
    {
        return $this->audioUri;
    }

    public function setAudioUri(string $audioUri): self
    {
        $this->audioUri = $audioUri;

        return $this;
    }

    public function getCrawlerOutput(): array
    {
        return $this->crawlerOutput;
    }

    public function setCrawlerOutput(array $crawlerOutput): self
    {
        $this->crawlerOutput = $crawlerOutput;

        return $this;
    }
}
