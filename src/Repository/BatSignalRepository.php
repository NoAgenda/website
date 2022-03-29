<?php

namespace App\Repository;

use App\Entity\BatSignal;
use App\Entity\Episode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BatSignal|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatSignal|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatSignal[]    findAll()
 * @method BatSignal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatSignalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatSignal::class);
    }

    public function exists(BatSignal $signal): bool
    {
        $existing = $this->findOneBy([
            'code' => $signal->getCode(),
            'deployedAt' => $signal->getDeployedAt(),
        ]);

        return null !== $existing;
    }

    public function findOneByEpisode(Episode $episode): ?BatSignal
    {
        $builder = $this->createQueryBuilder('signal');

        $timespanEnd = new \DateTime();
        $timespanEnd->setTimestamp($episode->getPublishedAt()->getTimestamp());
        $timespanEnd->add(new \DateInterval('P1D'));

        $builder
            ->where($builder->expr()->between('signal.deployedAt', ':timespanStart', ':timespanEnd'))
            ->setParameter('timespanStart', $episode->getPublishedAt()->format('Y-m-d H:i:s'))
            ->setParameter('timespanEnd', $timespanEnd->format('Y-m-d H:i:s'))
        ;

        $query = $builder->getQuery();

        return $query->getOneOrNullResult();
    }
}
