<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Octopod\PodcastBundle\Entity\Episode as BaseEpisode;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeRepository")
 * @ORM\Table(name="na_episode")
 */
class Episode extends BaseEpisode
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
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $cover = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $special = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $chatMessages = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $transcript = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $betaTranscript = false;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $shownotesUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $chatNotice;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $recordedAt;

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

    public function hasBetaTranscript(): bool
    {
        return $this->betaTranscript;
    }

    public function setBetaTranscript(bool $betaTranscript): self
    {
        $this->betaTranscript = $betaTranscript;

        return $this;
    }

    public function getShownotesUrl(): ?string
    {
        return $this->shownotesUrl;
    }

    public function setShownotesUrl(?string $uri): self
    {
        $this->shownotesUrl = $uri;

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

    public function getRecordedAt(): ?\DateTimeInterface
    {
        return $this->recordedAt;
    }

    public function setRecordedAt(?\DateTimeInterface $recordedAt): self
    {
        $this->recordedAt = $recordedAt;

        return $this;
    }
}
