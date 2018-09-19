<?php

namespace App\Controller;

use App\Entity\UserToken;
use App\Repository\UserTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("", name="token_")
 */
class TokenController extends Controller
{
    private $authChecker;
    private $entityManager;
    private $repository;

    public function __construct(
        AuthorizationCheckerInterface $authChecker,
        EntityManagerInterface $entityManager,
        UserTokenRepository $repository
    )
    {
        $this->authChecker = $authChecker;
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    /**
     * @Route("/token", name="create", methods="POST")
     */
    public function create(Request $request): Response
    {
        if ($this->authChecker->isGranted('ROLE_USER')) {
            return JsonResponse::create();
        }

        $string = $request->cookies->get('guest_token');

        if (!$string) {
            $string = $this->generateToken();
        }

        $token = $this->repository->findOneBy(['token' => $string]);

        if (!$token) {
            $token = (new UserToken)
                ->setToken($string)
                ->addCurrentIpAddress()
            ;

            $this->entityManager->persist($token);
            $this->entityManager->flush();
        }

        $response = JsonResponse::create();
        $response->headers->setCookie(new Cookie('guest_token', $string));

        return $response;
    }

    private function generateToken(): string
    {
        return sprintf( '%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
