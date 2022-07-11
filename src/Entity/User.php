<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[Entity(repositoryClass: UserRepository::class)]
#[Table(name: 'na_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id = null;

    #[ManyToOne(targetEntity: User::class)]
    private ?User $master = null;

    #[OneToOne(mappedBy: 'user', targetEntity: UserAccount::class, cascade: ['persist', 'remove'])]
    private ?UserAccount $account = null;

    #[OneToMany(mappedBy: 'user', targetEntity: UserToken::class, orphanRemoval: true)]
    private Collection $tokens;

    #[Column(type: 'uuid', unique: true)]
    private string $userIdentifier;

    #[Column(type: 'boolean')]
    private bool $banned = false;

    #[Column(type: 'boolean')]
    private bool $hidden = false;

    #[Column(type: 'boolean')]
    private bool $reviewed = false;

    #[Column(type: 'boolean')]
    private bool $needsReview = false;

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->tokens = new ArrayCollection();
        $this->userIdentifier = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaster(): ?User
    {
        return $this->master;
    }

    public function setMaster(?User $master): self
    {
        $this->master = $master;

        return $this;
    }

    public function getAccount(): ?UserAccount
    {
        return $this->account;
    }

    public function setAccount(UserAccount $account): self
    {
        if ($account->getUser() !== $this) {
            $account->setUser($this);
        }

        $this->account = $account;

        return $this;
    }

    /**
     * @return Collection<int, UserToken>
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function addToken(UserToken $token): self
    {
        if (!$this->tokens->contains($token)) {
            $this->tokens[] = $token;
            $token->setUser($this);
        }

        return $this;
    }

    public function removeToken(UserToken $token): self
    {
        if ($this->tokens->removeElement($token)) {
            if ($token->getUser() === $this) {
                $token->setUser(null);
            }
        }

        return $this;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function isBanned(): bool
    {
        return $this->banned;
    }

    public function setBanned(bool $banned): self
    {
        $this->banned = $banned;

        return $this;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function isReviewed(): bool
    {
        return $this->reviewed;
    }

    public function setReviewed(bool $reviewed): self
    {
        $this->reviewed = $reviewed;

        if ($this->reviewed) {
            $this->needsReview = false;
        }

        return $this;
    }

    public function getNeedsReview(): bool
    {
        return $this->needsReview;
    }

    public function setNeedsReview(bool $needsReview): self
    {
        $this->needsReview = $needsReview;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getRoles(): array
    {
        return $this->account?->getRoles() ?? ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        return $this->account?->getPassword();
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        $this->account?->eraseCredentials();
    }

    public function getUsername(): string
    {
        return $this->account?->getUsername() ?? 'Anonymous Producer';
    }

    public function isRegistered(): bool
    {
        return null !== $this->account;
    }

    public function isPublic(): bool
    {
        return $this->reviewed && !$this->hidden && !$this->banned;
    }

    public function isAdmin(): bool
    {
        return $this->account?->isAdmin() ?? false;
    }

    public function isMod(): bool
    {
        return $this->account?->isMod() ?? false;
    }

    public function getIpAddresses(): array
    {
        $collection = [];

        foreach ($this->getTokens() as $token) {
            $collection = array_merge($collection, $token->getIpAddresses());
        }

        return array_unique($collection);
    }

    public function getStatus(): string
    {
        if ($this->master) {
            return 'Waiting to be Merged';
        }

        if ($this->banned) {
            return 'Banned';
        } elseif ($this->hidden) {
            return 'Hidden';
        } elseif ($this->needsReview) {
            return 'Waiting for Review';
        } elseif (!$this->reviewed) {
            return 'Douchebag';
        }

        if (!$this->account) {
            return 'Anonymous';
        }

        if ($this->isAdmin()) {
            return 'Administrator';
        } elseif ($this->isMod()) {
            return 'Moderator';
        }

        return 'Registered';
    }
}
