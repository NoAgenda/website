<?php

namespace App\Entity;

use App\Repository\BatSignalRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: BatSignalRepository::class)]
#[Table(name: 'na_bat_signal')]
class BatSignal
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id;

    #[Column(type: 'string', length: 16)]
    private ?string $code;

    #[Column(type: 'datetime')]
    private ?\DateTimeInterface $deployedAt;

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
