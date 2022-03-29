<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }

    public function __construct(
        private RouterInterface $router,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$token = $this->tokenStorage->getToken()) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ($user->isHidden()) {
            $route = $event->getRequest()->attributes->get('_route');

            if (!str_starts_with($route, 'security_') && !str_starts_with($route, 'account_')) {
                $event->setResponse(new RedirectResponse($this->router->generate('account_status')));
            }
        }
    }
}
