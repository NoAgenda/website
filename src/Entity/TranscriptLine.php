<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TranscriptLineRepository")
 */
class TranscriptLine
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
     * @var Show
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Show")
     * @ORM\JoinColumn(nullable=false)
     */
    private $show;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $timestamp;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $duration;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $text;

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

    public function getShow(): Show
    {
        return $this->show;
    }

    public function setShow(Show $show): self
    {
        $this->show = $show;

        return $this;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

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
