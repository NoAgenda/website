<?php

namespace App\EventListener\Doctrine;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserSubscriber implements EventSubscriber
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function getSubscribedEvents(): array
    {
        return [
            'prePersist',
            'preUpdate',
        ];
    }

    public function prePersist(LifecycleEventArgs $event)
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

    public function preUpdate(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();

        if (!$user instanceof User) {
            return;
        }

        $this->hashPassword($user);
    }

    private function hashPassword(User $user)
    {
        if (!$user->getPlainPassword()) {
            return;
        }

        $user->generateSalt();

        $password = $this->encoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($password);
    }
}
