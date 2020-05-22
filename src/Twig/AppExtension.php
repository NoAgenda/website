<?php

namespace App\Twig;

use App\UserTokenManager;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private $userTokenManager;

    public function __construct(UserTokenManager $userTokenManager)
    {
        $this->userTokenManager = $userTokenManager;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('desimplifyDate', [$this, 'desimplifyDate']),
            new TwigFilter('desimplifyTime', [$this, 'desimplifyTime']),
            new TwigFilter('prettyTimestamp', [$this, 'prettyTimestamp']),
            new TwigFilter('visualTimestamp', [$this, 'visualTimestamp']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('desimplifyDate', [$this, 'desimplifyDate']),
            new TwigFunction('desimplifyTime', [$this, 'desimplifyTime']),
            new TwigFunction('prettyTimestamp', [$this, 'prettyTimestamp']),
            new TwigFunction('visualTimestamp', [$this, 'visualTimestamp']),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'authenticated' => $this->userTokenManager->isAuthenticated(),
        ];
    }

    public function desimplifyDate($date): string
    {
        return implode('-', [substr($date, 0, 4), substr($date, 4, 2), substr($date, 6, 2)]);
    }

    public function desimplifyTime($time): string
    {
        return implode(':', [substr($time, 0, 2), substr($time, 2, 2), substr($time, 2, 2)]);
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
}
