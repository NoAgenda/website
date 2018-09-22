<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodePartCorrectionVoteRepository")
 * @ORM\Table(name="na_episode_part_correction_vote")
 */
class EpisodePartCorrectionVote
{
    const VOTES = [
        self::VOTE_SUPPORT,
        self::VOTE_REJECT,
        self::VOTE_QUESTION,
    ];

    const VOTE_SUPPORT = 'support';
    const VOTE_REJECT = 'reject';
    const VOTE_QUESTION = 'question';

    /**
     * @var integer
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var EpisodePartCorrection
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\EpisodePartCorrection", inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $correction;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true)
     */
    private $creator;

    /**
     * @var UserToken|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserToken")
     * @ORM\JoinColumn(nullable=true)
     */
    private $creatorToken;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $supported;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $rejected;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $questioned;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @param User|string $creator
     *
     * @return EpisodePartCorrectionVote
     */
    public static function create(EpisodePartCorrection $correction, $creator, string $vote): self
    {
        if (!in_array($vote, self::VOTES)) {
            throw new \RuntimeException(sprintf('Invalid vote "%s".', $vote));
        }

        $instance = new self;
        $instance->setCorrection($correction);

        if ($creator instanceof User) {
            $instance->setCreator($creator);
        }
        else if ($creator instanceof UserToken) {
            $instance->setCreatorToken($creator);
        }
        else {
            throw new \LogicException;
        }

        static $methods = [
            self::VOTE_SUPPORT => 'setSupported',
            self::VOTE_REJECT => 'setRejected',
            self::VOTE_QUESTION => 'setQuestioned',
        ];

        call_user_func([$instance, $methods[$vote]]);

        return $instance;
    }

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable;
    }

    public function __toString(): string
    {
        $methods = [
            self::VOTE_SUPPORT => 'getSupported',
            self::VOTE_REJECT => 'getRejected',
            self::VOTE_QUESTION => 'getQuestioned',
        ];

        $filteredMethod = array_filter($methods, function ($method) {
            return call_user_func([$this, $method]);
        });

        $vote = current(array_keys($filteredMethod));

        return sprintf('%s vote by %s', ucfirst($vote), $this->getCreator() ?? 'guest');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCorrection(): ?EpisodePartCorrection
    {
        return $this->correction;
    }

    public function setCorrection(?EpisodePartCorrection $correction): self
    {
        $this->correction = $correction;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreatorToken(): ?UserToken
    {
        return $this->creatorToken;
    }

    public function setCreatorToken(?UserToken $creatorToken): self
    {
        $this->creatorToken = $creatorToken;

        return $this;
    }

    public function getSupported(): ?bool
    {
        return $this->supported;
    }

    public function setSupported(): self
    {
        $this->supported = true;
        $this->rejected = false;
        $this->questioned = false;

        return $this;
    }

    public function getRejected(): ?bool
    {
        return $this->rejected;
    }

    public function setRejected(): self
    {
        $this->supported = false;
        $this->rejected = true;
        $this->questioned = false;

        return $this;
    }

    public function getQuestioned(): ?bool
    {
        return $this->questioned;
    }

    public function setQuestioned(): self
    {
        $this->supported = false;
        $this->rejected = true;
        $this->questioned = false;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
