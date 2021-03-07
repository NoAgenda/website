<?php

namespace App\Updates;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

abstract class AbstractUpdater implements ServiceSubscriberInterface
{
    public static function getSubscribedServices()
    {
        return [
            'mailer' => \Swift_Mailer::class,
            'request_stack' => RequestStack::class,
            'router' => RouterInterface::class,
            'twig' => Environment::class,
        ];
    }

    /**
     * @var ServiceLocator
     */
    protected $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function getAuthorEmail(): string
    {
        return $_SERVER['MAILER_FROM'];
    }

    public function getAuthorName(): string
    {
        return $_SERVER['MAILER_FROM_AUTHOR'];
    }

    public function generateUrl(string $route, array $parameters = []): string
    {
        $router = $this->locator->get('router');

        return $router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function renderTemplate(string $template, array $variables = []): string
    {
        $twig = $this->locator->get('twig');

        return $twig->render($template, $variables);
    }

    public function sendMessage(\Swift_Message $message): bool
    {
        $mailer = $this->locator->get('mailer');

        return (bool) $mailer->send($message);
    }
}
