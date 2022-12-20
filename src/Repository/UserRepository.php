<?php

namespace App\Repository;

use App\Entity\EpisodeChapter;
use App\Entity\EpisodeChapterDraft;
use App\Entity\FeedbackItem;
use App\Entity\FeedbackVote;
use App\Entity\User;
use App\Entity\UserAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Uid\Uuid;

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

    /**
     * @return User[]
     */
    public function findInactiveUsers(): array
    {
        $builder = $this->createQueryBuilder('user');

        $createdBefore = new \DateTime();
        $createdBefore->sub(new \DateInterval('P1M'));

        $users = $builder
            ->leftJoin('user.account', 'account')
            ->leftJoin(User::class, 'delegate', Join::WITH, $builder->expr()->eq('delegate.master', 'user.id'))
            ->leftJoin(EpisodeChapter::class, 'chapter', Join::WITH, $builder->expr()->eq('chapter.creator', 'user.id'))
            ->leftJoin(EpisodeChapterDraft::class, 'chapter_draft', Join::WITH, $builder->expr()->eq('chapter_draft.creator', 'user.id'))
            ->leftJoin(FeedbackItem::class, 'feedback_item', Join::WITH, $builder->expr()->eq('feedback_item.creator', 'user.id'))
            ->leftJoin(FeedbackVote::class, 'feedback_vote', Join::WITH, $builder->expr()->eq('feedback_vote.creator', 'user.id'))
            ->andWhere(
                $builder->expr()->lt('user.createdAt', ':createdBefore'),
                $builder->expr()->isNull('user.master'),
                $builder->expr()->isNull('delegate'),
                $builder->expr()->isNull('chapter'),
                $builder->expr()->isNull('chapter_draft'),
                $builder->expr()->isNull('feedback_item'),
                $builder->expr()->isNull('feedback_vote'),
            )
            ->setParameter('createdBefore', $createdBefore->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        return array_filter($users, fn (User $user) => !$user->isMod());
    }

    /**
     * @return User[]
     */
    public function findUnreviewedUsers(): array
    {
        return $this->findBy([
            'banned' => false,
            'hidden' => false,
            'needsReview' => true,
        ]);
    }

    public function countUnreviewedUsers(): int
    {
        return $this->count([
            'banned' => false,
            'hidden' => false,
            'needsReview' => true,
        ]);
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

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        if (Uuid::isValid($identifier)) {
            return $this->findOneBy([
                'userIdentifier' => Uuid::fromString($identifier)->toBinary(),
            ]);
        }

        return $this->getEntityManager()
            ->getRepository(UserAccount::class)
            ->loadUserByIdentifier($identifier)
            ?->getUser();
    }

    public function loadUserByUsername(string $username): ?User
    {
        return $this->loadUserByIdentifier($username);
    }
}
