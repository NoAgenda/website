<?php

namespace App\EventListener\Doctrine;

use App\Entity\UserAccount;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserAccountEntityListener implements EventSubscriber
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
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
        $account = $event->getEntity();

        if ($account instanceof UserAccount) {
            if (null === $account->getPlainPassword()) {
                throw new \LogicException('A new account can\'t be created without a password.');
            }

            $this->hashPassword($account);
        }
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $account = $event->getEntity();

        if ($account instanceof UserAccount && null !== $account->getPlainPassword()) {
            $this->hashPassword($account);
        }
    }

    private function hashPassword(UserAccount $account): void
    {
        $password = $this->passwordHasher->hashPassword($account, $account->getPlainPassword());
        $account->setPassword($password);

        $account->eraseCredentials();
    }
}
