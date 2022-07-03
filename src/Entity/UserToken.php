<?php

namespace App\Entity;

use App\Repository\UserTokenRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Uid\Uuid;

#[Entity(repositoryClass: UserTokenRepository::class)]
#[Table(name: 'na_user_token')]
class UserToken
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id = null;

    #[ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'tokens')]
    #[JoinColumn(nullable: false)]
    private ?User $user = null;

    #[Column(type: 'string', length: 255)]
    private string $publicToken;

    #[Column(type: 'array')]
    private array $ipAddresses = [];

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->publicToken = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPublicToken(): ?string
    {
        return $this->publicToken;
    }

    public function setPublicToken(string $publicToken): self
    {
        $this->publicToken = $publicToken;

        return $this;
    }

    public function getIpAddresses(): array
    {
        return $this->ipAddresses;
    }

    public function addIpAddress(string $ipAddress): self
    {
        if (!in_array($ipAddress, $this->ipAddresses)) {
            $this->ipAddresses[] = $ipAddress;
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
