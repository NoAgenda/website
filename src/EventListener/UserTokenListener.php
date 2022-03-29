<?php

namespace App\EventListener;

use App\Repository\UserTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class UserTokenListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserTokenRepository $userTokenRepository,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$string = $request->cookies->get('guest_token')) {
            return;
        }

        if (!$token = $this->userTokenRepository->findOneBy(['token' => $string])) {
            return;
        }

        if (!in_array($currentIp = $request->getClientIp(), $token->getIpAddresses())) {
            $token->addIpAddress($currentIp);

            $this->entityManager->persist($token);
            $this->entityManager->flush();
        }
    }
}
