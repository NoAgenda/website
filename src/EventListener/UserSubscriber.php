<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserSubscriber implements EventSubscriberInterface
{
    private $router;
    private $tokenStorage;

    public function __construct(RouterInterface $router, TokenStorageInterface $tokenStorage)
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->getUser();

        if (!$user) {
            return;
        }

        if ($user->isHidden()) {
            $route = $event->getRequest()->attributes->get('_route');

            if (0 !== strpos($route, 'security_') && 0 !== strpos($route, 'account_')) {
                $event->setResponse(new RedirectResponse($this->router->generate('account_status')));
            }
        }
    }

    private function getUser(): ?User
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }
}
