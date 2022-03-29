<?php

namespace App\EventListener\Doctrine;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserEntityListener implements EventSubscriber
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            'prePersist',
            'preUpdate',
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $user = $event->getEntity();

        if (!$user instanceof User) {
            return;
        }

        if (!$user->getPlainPassword()) {
            $user->setPlainPassword(mt_rand(1000, 9999));
        }

        $this->hashPassword($user);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $user = $event->getEntity();

        if (!$user instanceof User) {
            return;
        }

        $this->hashPassword($user);
    }

    private function hashPassword(User $user): void
    {
        if (!$user->getPlainPassword()) {
            return;
        }

        $user->generateSalt();

        $password = $this->passwordHasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($password);
    }
}
