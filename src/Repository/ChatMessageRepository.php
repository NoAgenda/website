<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\Episode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ChatMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatMessage[]    findAll()
 * @method ChatMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * @return ChatMessage[]
     */
    public function findByEpisode(Episode $episode): array
    {
        return $this->findBy(['episode' => $episode], ['postedAt' => 'ASC', 'source' => 'DESC', 'createdAt' => 'ASC']);
    }

    /**
     * @return ChatMessage[]
     */
    public function findByEpisodeCollection(Episode $episode, int $collection): array
    {
        $builder = $this->createQueryBuilder('message');

        $builder
            ->where($builder->expr()->andX(
                $builder->expr()->eq('message.episode', ':episode'),
                $builder->expr()->gte('message.postedAt', ':collectionStart'),
                $builder->expr()->lte('message.postedAt', ':collectionEnd')
            ))
            ->setParameter('episode', $episode->getId())
            ->setParameter('collectionStart', $collection * 1000)
            ->setParameter('collectionEnd', ($collection + 1) * 1000)
            ->addOrderBy('message.postedAt', 'ASC')
            ->addOrderBy('message.source', 'DESC')
            ->addOrderBy('message.createdAt', 'ASC')
        ;

        $query = $builder->getQuery();

        return $query->getResult();
    }
}
