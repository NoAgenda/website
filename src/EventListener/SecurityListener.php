<?php

namespace App\EventListener;

use App\Entity\User;
use App\Message\MergeUser;
use App\Repository\UserRepository;
use App\Repository\UserTokenRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

class SecurityListener implements EventSubscriberInterface
{
    private bool $revoke = false;

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
            'kernel.response' => 'onKernelResponse',
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        ];
    }

    public function __construct(
        private readonly UserTokenRepository $userTokenRepository,
        private readonly UserRepository $userRepository,
        private readonly MessageBusInterface $commandBus,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        /** @var User $user */
        if (null === $user = $this->tokenStorage->getToken()?->getUser()) {
            return;
        }

        $request = $event->getRequest();

        if (null === $user->getAccount()) {
            $userToken = $this->userTokenRepository->findOneByPublicToken($request->cookies->get('auth_token'));

            if (!in_array($currentIp = $request->getClientIp(), $userToken->getIpAddresses())) {
                $userToken->addIpAddress($currentIp);

                $this->userTokenRepository->persist($userToken, true);
            }
        }

        if ($user->isBanned() || $user->isHidden()) {
            $route = $event->getRequest()->attributes->get('_route');

            if (!str_starts_with($route, 'security_') && !str_starts_with($route, 'account_')) {
                $event->setResponse(new RedirectResponse($this->router->generate('account_status')));
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->revoke) {
            return;
        }

        $event->getResponse()->headers->clearCookie('auth_token');
        $event->getResponse()->headers->clearCookie('guest_token');
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();

        if (null === $user->getAccount() || !($request->cookies->has('auth_token') || $request->cookies->has('guest_token'))) {
            return;
        }

        $delegateUser = $this->userTokenRepository
            ->findOneByPublicToken($request->cookies->get('auth_token') ?? $request->cookies->has('guest_token'))
            ?->getUser();

        if ($delegateUser) {
            $delegateUser->setMaster($user);

            $this->userRepository->persist($delegateUser, true);

            $this->commandBus->dispatch(new MergeUser($delegateUser->getId()));
        }

        $this->revoke = true;
    }
}
