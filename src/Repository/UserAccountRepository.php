<?php

namespace App\Repository;

use App\Entity\UserAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use function Symfony\Component\String\u;

/**
 * @method UserAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAccount[]    findAll()
 * @method UserAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method UserAccount|null findOneBy(array $criteria, array $orderBy = null)
 */
class UserAccountRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAccount::class);
    }

    public function findByUserInput(string $input): ?UserAccount
    {
        $builder = $this->createQueryBuilder('user_account');

        $query = $builder
            ->orWhere(
                $builder->expr()->eq('user_account.usernameCanonical', ':username'),
                $builder->expr()->eq('user_account.emailCanonical', ':email'),
            )
            ->setParameter('username', UserAccount::canonicalize($input))
            ->setParameter('email', u($input)->lower())
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function persist(UserAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function loadUserByIdentifier(string $identifier): ?UserAccount
    {
        return $this->findOneBy([
            'usernameCanonical' => UserAccount::canonicalize($identifier),
        ]);
    }

    public function loadUserByUsername(string $username): ?UserAccount
    {
        return $this->loadUserByIdentifier($username);
    }
}
