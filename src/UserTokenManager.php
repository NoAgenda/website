<?php

namespace App;

use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\UserTokenRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserTokenManager
{
    private $requestStack;
    private $tokenStorage;
    private $userTokenRepository;

    /**
     * @var User|UserToken|null|false
     */
    private $current = false;

    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage, UserTokenRepository $userTokenRepository)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->userTokenRepository = $userTokenRepository;
    }

    public function isAuthenticated(): bool
    {
        return $this->getCurrent() !== null;
    }

    /**
     * @return User|UserToken|null
     */
    public function getCurrent()
    {
        if ($this->current === false) {
            $this->loadCurrent();
        }

        return $this->current;
    }

    private function loadCurrent(): void
    {
        $securityToken = $this->tokenStorage->getToken();

        if ($securityToken) {
            $user = $securityToken->getUser();

            if ($user instanceof UserInterface) {
                $this->current = $user;

                return;
            }
        }

        $request = $this->requestStack->getMasterRequest();

        if ($request) {
            $tokenValue = $request->cookies->get('guest_token');

            if ($tokenValue) {
                $token = $this->userTokenRepository->findOneBy(['token' => $tokenValue]);

                if ($token) {
                    $this->current = $token;

                    return;
                }
            }
        }

        $this->current = null;
    }

    public function fill($entity): void
    {
        if ($this->current instanceof User) {
            $entity->setCreator($this->current);
        } else if ($this->current instanceof UserToken) {
            $entity->setCreatorToken($this->current);
        }
    }
}
