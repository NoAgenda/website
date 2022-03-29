<?php

namespace App\Updates;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractUpdater
{
    public function __construct(
        protected MailerInterface $mailer,
        protected RequestStack $requestStack,
        protected RouterInterface $router,
    ) {}

    protected function getAuthorEmail(): string
    {
        return $_SERVER['MAILER_FROM'];
    }

    protected function getAuthorName(): string
    {
        return $_SERVER['MAILER_FROM_AUTHOR'];
    }

    protected function generateUrl(string $route, array $parameters = []): string
    {
        return $this->router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
