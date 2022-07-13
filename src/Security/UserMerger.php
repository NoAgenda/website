<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\UserCreatedInterface;
use App\Entity\UserToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UserMerger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {}

    public function merge(User $user): void
    {
        if (null === $master = $user->getMaster()) {
            throw new \RuntimeException(sprintf('User %s could not be merged because it doesn\'t have a master.', $user->getUserIdentifier()));
        }

        if (null !== $user->getAccount()) {
            throw new \RuntimeException(sprintf('User %s could not be merged because it there\'s an account connected to it.', $user->getUserIdentifier()));
        }

        $transferredCounts = [];

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $metadataCollection = $this->entityManager->getMetadataFactory()->getAllMetadata();

            foreach ($metadataCollection as $metadata) {
                $className = $metadata->getName();

                if (is_subclass_of($className, UserCreatedInterface::class, true)) {
                    $transferredCounts[$className] = $this->transfer($user, $master, $className);
                }
            }

            $transferredCounts[UserToken::class] = $this->transferTokens($user, $master);

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();

            throw new \Exception(sprintf('Failed to merge user %s: %s', $user->getUserIdentifier(), $exception->getMessage()), 0, $exception);
        }

        $message = sprintf('Merged user %s with entities:', $user->getUserIdentifier());

        foreach ($transferredCounts as $entityName => $count) {
            $message .= sprintf("\n%s: %s", str_replace('App\\Entity\\', '', $entityName), $count);
        }

        $this->logger->info($message);
    }

    private function transfer(User $user, User $master, string $className): int
    {
        $entityRepository = $this->entityManager->getRepository($className);
        $entities = $entityRepository->findBy(['creator' => $user]);

        /** @var UserCreatedInterface $entity */
        foreach ($entities as $entity) {
            $entity->setCreator($master);

            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();

        return count($entities);
    }

    private function transferTokens(User $user, User $master): int
    {
        $affected = count($user->getTokens());

        foreach ($user->getTokens() as $userToken) {
            $userToken->setUser($master);

            $this->entityManager->persist($userToken);
        }

        $this->entityManager->flush();

        return $affected;
    }
}
