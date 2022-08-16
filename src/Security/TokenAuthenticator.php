<?php

namespace App\Security;

use App\Repository\UserTokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserTokenRepository $userTokenRepository,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->cookies->has('auth_token') || $request->cookies->has('guest_token');
    }

    public function authenticate(Request $request): Passport
    {
        $publicToken = $request->cookies->get('auth_token') ?? $request->cookies->get('guest_token');

        return new SelfValidatingPassport(new UserBadge($publicToken, function ($publicToken) {
            $token = $this->userTokenRepository->findOneByPublicToken($publicToken);
            $user = $token->getUser();

            $user->setCurrentToken($token);

            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
