<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BatSignalRepository")
 * @ORM\Table("na_bat_signal")
 */
class BatSignal
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
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $processed;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     */
    private $deployedAt;

    public function __construct()
    {
        $this->processed = false;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isProcessed(): bool
    {
        return $this->processed ?? false;
    }

    public function setProcessed(bool $processed): self
    {
        $this->processed = $processed;

        return $this;
    }

    public function getDeployedAt(): ?\DateTimeInterface
    {
        return $this->deployedAt;
    }

    public function setDeployedAt(\DateTimeInterface $deployedAt): self
    {
        $this->deployedAt = $deployedAt;

        return $this;
    }
}
