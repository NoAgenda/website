<?php

namespace App\Controller;

use App\Entity\NotificationSubscription;
use App\Repository\NotificationSubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(
        private readonly NotificationSubscriptionRepository $notificationSubscriptionRepository,
    ) {}

    #[Route('/auth')]
    public function authenticationInfo(?UserInterface $user): Response
    {
        if (!$user) {
            return new JsonResponse(null);
        }

        return new JsonResponse([
            'authenticated' => true,
            'registered' => $user->isRegistered(),
            'username' => $user->getUsername(),
            'admin' => $user->isAdmin(),
            'mod' => $user->isMod(),
        ]);
    }

    #[Route('/livestream')]
    public function livestreamInfo(): Response
    {
        $livestreamInfoPath = sprintf('%s/livestream_info.json', $_SERVER['APP_STORAGE_PATH']);

        if (!file_exists($livestreamInfoPath)) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $info = json_decode(file_get_contents($livestreamInfoPath));

        return new JsonResponse($info);
    }

    #[Route('/notifications/subscribe/{type}')]
    public function registerNotificationSubscription(Request $request, string $type): Response
    {
        $rawSubscription = $request->getContent();

        if (str_contains($rawSubscription, 'web.push.apple.com')) {
            // Prevent Apple browsers from subscribing. All requests to Apple servers get a timeout
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        $subscriptionData = json_decode($rawSubscription, true);
        if (!isset($subscriptionData['endpoint'])) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        if (!$this->notificationSubscriptionRepository->match($rawSubscription, $type)) {
            $notificationSubscription = (new NotificationSubscription())
                ->setRawSubscription($rawSubscription)
                ->setType($type);

            $this->notificationSubscriptionRepository->persist($notificationSubscription, true);
        }

        return new Response();
    }

    #[Route('/notifications/unsubscribe/{type}')]
    public function removeNotificationSubscription(Request $request, string $type): Response
    {
        $rawSubscription = $request->getContent();

        $subscriptionData = json_decode($rawSubscription, true);
        if (!isset($subscriptionData['endpoint'])) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        if ($notificationSubscription = $this->notificationSubscriptionRepository->match($rawSubscription, $type)) {
            $this->notificationSubscriptionRepository->remove($notificationSubscription, true);
        }

        return new Response();
    }
}
