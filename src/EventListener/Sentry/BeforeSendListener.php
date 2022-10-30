<?php

namespace App\EventListener\Sentry;

use Sentry\Event;
use Sentry\EventHint;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BeforeSendListener
{
    private $ignoredExceptions = [
        // http
        AccessDeniedHttpException::class,
        BadRequestHttpException::class,
        MethodNotAllowedHttpException::class,
        NotFoundHttpException::class,

        // security
        AccessDeniedException::class,
    ];

    public function __invoke(Event $event, ?EventHint $hint): ?Event
    {
        foreach ($this->ignoredExceptions as $ignoredException) {
            if ($hint?->exception instanceof $ignoredException) {
                return null;
            }
        }

        return $event;
    }
}
