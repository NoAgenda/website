<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserTokenRepository")
 * @ORM\Table(name="na_user_token")
 */
class UserToken
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
     * @ORM\Column(type="string", length=255)
     */
    private $token;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $ipAddresses;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    public function __construct()
    {
        $this->ipAddresses = [];
        $this->createdAt = new \DateTimeImmutable;
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
        if (false !== array_search($ipAddress, $this->ipAddresses)) {
            return $this;
        }

        $this->ipAddresses[] = $ipAddress;

        return $this;
    }

    public function addCurrentIpAddress(): self
    {
        $this->addIpAddress($_SERVER['REMOTE_ADDR'] ?? 'null');

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
