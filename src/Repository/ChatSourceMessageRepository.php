<?php

namespace App\Repository;

use App\Entity\ChatSourceMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ChatSourceMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatSourceMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatSourceMessage[]    findAll()
 * @method ChatSourceMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatSourceMessageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ChatSourceMessage::class);
    }
}
