<?php

namespace App\EventListener\Doctrine;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserEntityListener implements EventSubscriber
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            'prePersist',
            'preUpdate',
        ];
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        $user = $event->getObject();

        if ($user instanceof User) {
            if (null === $user->getPlainPassword()) {
                throw new \LogicException('A new user can\'t be created without a password.');
            }

            $this->hashPassword($user);
        }
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $user = $event->getObject();

        if ($user instanceof User && null !== $user->getPlainPassword()) {
            $this->hashPassword($user);
        }
    }

    private function hashPassword(User $user): void
    {
        $password = $this->passwordHasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($password);

        $user->eraseCredentials();
    }
}
