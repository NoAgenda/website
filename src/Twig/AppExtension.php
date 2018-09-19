<?php

namespace App\Twig;

use App\Repository\UserTokenRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private $requestStack;
    private $tokenStorage;
    private $userTokenRepository;

    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage, UserTokenRepository $userTokenRepository)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->userTokenRepository = $userTokenRepository;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('prettyTimestamp', [$this, 'prettyTimestamp']),
            new TwigFilter('visualTimestamp', [$this, 'visualTimestamp']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('prettyTimestamp', [$this, 'prettyTimestamp']),
            new TwigFunction('visualTimestamp', [$this, 'visualTimestamp']),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'authenticated' => $this->isAuthenticated(),
            'analytics_code' => $_SERVER['APP_ANALYTICS_CODE'] ?? false,
        ];
    }

    public function prettyTimestamp($value): string
    {
        $value = (int) $value;

        $hours = floor($value / 60 / 60);
        $value = $value - ($hours * 60 * 60);

        $minutes = floor($value / 60);
        $value = $value - ($minutes * 60);

        $seconds = (string) $value;
        $seconds = strlen($seconds) === 1 ? '0' . $seconds : $seconds;

        if ($hours == 0) {
            return implode(':', [$minutes, $seconds]);
        }

        $minutes = strlen($minutes) == 1 ? '0' . $minutes : $minutes;

        return implode(':', [$hours, $minutes, $seconds]);
    }

    public function visualTimestamp($value): string
    {
        $value = (int) $value;

        $hours = floor($value / 60 / 60);
        $value = $value - ($hours * 60 * 60);

        $minutes = floor($value / 60);
        $value = $value - ($minutes * 60);

        if ($hours == 0) {
            return sprintf('%sm', $minutes);
        }

        return sprintf('%sh %sm', $hours, $minutes);
    }

    private function isAuthenticated(): bool
    {
        $token = $this->tokenStorage->getToken();

        if ($token && $token->getUser() instanceof UserInterface) {
            return true;
        }

        $request = $this->requestStack->getMasterRequest();

        if (!$request) {
            return false;
        }

        $string = $request->cookies->get('guest_token');

        $token = $this->userTokenRepository->findOneBy(['token' => $string]);

        if (!$token) {
            return false;
        }

        return true;
    }
}
