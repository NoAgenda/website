<?php

namespace App\Entity;

use App\Repository\UserTokenRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: UserTokenRepository::class)]
#[Table(name: 'na_user_token')]
class UserToken
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id;

    #[Column(type: 'string', length: 255)]
    private ?string $token;

    #[Column(type: 'array')]
    private array $ipAddresses = [];

    #[Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

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
