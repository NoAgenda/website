<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\UserTokenRepository;
use Symfony\Component\HttpFoundation\Cookie;
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
            if (!$userToken = $this->userTokenRepository->findOneByPublicToken($publicToken)) {
                $userToken = (new UserToken())
                    ->setUser(new User())
                    ->setPublicToken($publicToken);

                $this->userTokenRepository->addCurrentIpAddress($userToken);
            }

            $user = $userToken->getUser();
            $user->setCurrentToken($userToken);

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

    public function generateToken(Request $request, Response $response): void
    {
        $publicToken = $request->cookies->get('auth_token') ?? $request->cookies->get('guest_token');

        if (null !== $this->userTokenRepository->findOneBy(['publicToken' => $publicToken])) {
            return;
        }

        $token = (new UserToken())
            ->setUser(new User())
            ->addIpAddress($request->getClientIp());

        $this->userTokenRepository->persist($token, true);

        $response->headers->setCookie(new Cookie('auth_token', $token->getPublicToken(), strtotime('+33 months')));
    }
}
