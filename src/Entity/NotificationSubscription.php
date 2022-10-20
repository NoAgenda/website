<?php

namespace App\Entity;

use App\Repository\NotificationSubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;

#[ORM\Entity(repositoryClass: NotificationSubscriptionRepository::class)]
#[Table(name: 'na_notification_subscription')]
class NotificationSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $rawSubscription = null;

    #[ORM\Column(length: 32)]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRawSubscription(): ?string
    {
        return $this->rawSubscription;
    }

    public function setRawSubscription(string $rawSubscription): self
    {
        $this->rawSubscription = $rawSubscription;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSubscription(): ?array
    {
        return json_decode($this->rawSubscription, true);
    }
}
