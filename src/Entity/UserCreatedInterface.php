<?php

namespace App\Entity;

interface UserCreatedInterface
{
    public function getCreator(): ?User;
    public function setCreator(?User $user): self;

    public function getCreatedAt(): \DateTimeImmutable;
}
