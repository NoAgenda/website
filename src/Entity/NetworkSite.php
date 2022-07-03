<?php

namespace App\Entity;

use App\Repository\NetworkSiteRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: NetworkSiteRepository::class)]
#[Table(name: 'na_network_site')]
class NetworkSite
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id = null;

    #[Column(type: 'string', length: 255)]
    private ?string $name;

    #[Column(type: 'string', length: 255, nullable: true)]
    private ?string $icon;

    #[Column(type: 'text')]
    private ?string $description;

    #[Column(type: 'string', length: 255)]
    private ?string $uri;

    #[Column(type: 'string', length: 255)]
    private ?string $displayUri;

    #[Column(type: 'integer')]
    private ?int $priority = 1000;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    public function getDisplayUri(): ?string
    {
        return $this->displayUri;
    }

    public function setDisplayUri(string $displayUri): self
    {
        $this->displayUri = $displayUri;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
