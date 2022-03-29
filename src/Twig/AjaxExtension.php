<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AjaxExtension extends AbstractExtension
{
    public function __construct(
        private RouterInterface $router,
        private RequestStack $requestStack,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ajaxRequestPath', [$this, 'ajaxRequestPath']),
        ];
    }

    public function ajaxRequestPath(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $routeName = $request->attributes->get('_route', '');
        $routeParams = $request->attributes->get('_route_params', []);

        if (!$this->router->getRouteCollection()->get($routeName)) {
            // For some reason, Symfony doesn't give a valid route in some cases
            return '/';
        }

        return $this->router->generate($routeName, $routeParams);
    }
}
