<?php

namespace App\Repository;

use App\Entity\BatSignal;
use App\Entity\Episode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method BatSignal|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatSignal|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatSignal[]    findAll()
 * @method BatSignal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatSignalRepository extends ServiceEntityRepository
{
    private $episodeRepository;

    public function __construct(RegistryInterface $registry, EpisodeRepository $episodeRepository)
    {
        parent::__construct($registry, BatSignal::class);

        $this->episodeRepository = $episodeRepository;
    }

    /**
     * @deprecated use findOneByEpisode instead
     */
    public function findOneByCode($code): ?BatSignal
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findOneUnprocessed(): ?BatSignal
    {
        return $this->findOneBy(['processed' => false]);
    }

    /**
     * @param Episode|string $episode
     */
    public function findOneByEpisode($episode): ?BatSignal
    {
        if (!$episode instanceof Episode) {
            $episode = $this->episodeRepository->findOneByCode($episode);

            if (!$episode) {
                throw new \RuntimeException(sprintf('Invalid episode code "%s".', $episode));
            }
        }

        $builder = $this->createQueryBuilder('signal');

        $timespanEnd = new \DateTime;
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

    public function findOneByLatestEpisode(): ?BatSignal
    {
        $latestEpisode = $this->episodeRepository->findLatest();

        return $this->findOneByEpisode($latestEpisode);
    }
}
