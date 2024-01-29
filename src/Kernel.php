<?php

namespace App;

use Minishlink\WebPush\WebPush;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function process(ContainerBuilder $container): void
    {
        $vapidPrivateKey = $_SERVER['VAPID_PRIVATE_KEY'] ?? null;
        $vapidPublicKey = $_SERVER['VAPID_PUBLIC_KEY'] ?? null;

        if (!$vapidPrivateKey || !$vapidPublicKey) {
            $container->removeDefinition(WebPush::class);
        }
    }
}
