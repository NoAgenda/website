<?php

namespace App\Repository;

use App\Entity\UserToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method UserToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserToken[]    findAll()
 * @method UserToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method UserToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserToken|null findOneByPublicToken(string $publicToken, array $orderBy = null)
 */
class UserTokenRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct($registry, UserToken::class);
    }

    public function addCurrentIpAddress(UserToken $userToken): void
    {
        $request = $this->requestStack->getMainRequest();

        if (!in_array($currentIp = $request->getClientIp(), $userToken->getIpAddresses())) {
            $userToken->addIpAddress($currentIp);

            $this->persist($userToken, true);
        }
    }

    public function persist(UserToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
