<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function persist(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        $canonical = UserAccount::canonicalize($identifier);
        $userAccount = $this->getEntityManager()
            ->getRepository(UserAccount::class)
            ->findOneByUsernameCanonical($canonical);

        return $userAccount?->getUser();
    }

    public function loadUserByUsername(string $username): ?User
    {
        return $this->loadUserByIdentifier($username);
    }

    public function findUnreviewedUsers(): array
    {
        return $this->findBy([
            'banned' => false,
            'hidden' => false,
            'needsReview' => true,
        ]);
    }
}
