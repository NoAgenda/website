<?php

namespace App\Repository;

use App\Entity\NotificationSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationSubscription>
 *
 * @method NotificationSubscription[]    findAll()
 * @method NotificationSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method NotificationSubscription[]    findByType(string $type)
 * @method NotificationSubscription|null findOneBy(array $criteria, array $orderBy = null)
 */
class NotificationSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationSubscription::class);
    }

    public function persist(NotificationSubscription $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NotificationSubscription $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function match(string $rawSubscription, string $type): ?NotificationSubscription
    {
        return $this->findOneBy(['rawSubscription' => $rawSubscription, 'type' => $type]);
    }
}
