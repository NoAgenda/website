<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MastodonAnnouncement;
use Closure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<MastodonAnnouncement> */
class MastodonAnnouncementRepository extends ServiceEntityRepository
{
    private const ACCOUNTS = ['primary', 'secondary'];
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MastodonAnnouncement::class);
    }

    /**
     * Records the replacement before any deletion. The row lock serializes crawler invocations per account.
     *
     * @return string[] IDs that may now be deleted
     */
    public function recordPublishedStatus(string $account, string $statusId): array
    {
        $this->assertAccount($account);
        $entityManager = $this->getEntityManager();
        $entityManager->beginTransaction();

        try {
            $announcement = $this->findForUpdate($account);
            // Long-lived workers can otherwise flush a stale managed entity snapshot.
            $entityManager->refresh($announcement);
            $announcement->setCurrentStatusId($statusId);
            $entityManager->flush();
            $entityManager->commit();

            return $announcement->getPendingDeletionIds();
        } catch (\Throwable $exception) {
            $entityManager->rollback();

            throw $exception;
        }
    }

    /** Remove a deletion only after the provider accepted it. */
    public function clearPendingDeletion(string $account, string $statusId): void
    {
        $this->assertAccount($account);
        $entityManager = $this->getEntityManager();
        $entityManager->beginTransaction();

        try {
            $announcement = $this->findForUpdate($account);
            // Cleanup workers can hold an older identity-map snapshot than the publisher.
            $entityManager->refresh($announcement);
            if ($announcement->getCurrentStatusId() !== $statusId) {
                $announcement->clearPendingDeletion($statusId);
                $entityManager->flush();
            }
            $entityManager->commit();
        } catch (\Throwable $exception) {
            $entityManager->rollback();

            throw $exception;
        }
    }

    /** @return MastodonAnnouncement[] */
    public function findWithPendingDeletions(): array
    {
        $announcements = $this->findAll();
        foreach ($announcements as $announcement) {
            // A long-lived cleanup worker must not rely on its identity-map snapshot.
            $this->getEntityManager()->refresh($announcement);
        }

        return array_values(array_filter(
            $announcements,
            static fn (MastodonAnnouncement $announcement): bool => [] !== $announcement->getPendingDeletionIds(),
        ));
    }

    /** Serialize publish, persistence, and deletion per configured account. */
    public function withAccountLock(string $account, Closure $callback): void
    {
        $this->assertAccount($account);
        $connection = $this->getEntityManager()->getConnection();
        $lockName = 'noagenda_mastodon_' . $account;

        if (1 !== (int) $connection->fetchOne('SELECT GET_LOCK(?, 30)', [$lockName])) {
            throw new \RuntimeException(sprintf('Could not acquire Mastodon account lock for "%s".', $account));
        }

        try {
            $callback();
        } finally {
            $connection->fetchOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    private function findForUpdate(string $account): MastodonAnnouncement
    {
        $announcement = $this->createQueryBuilder('announcement')
            ->where('announcement.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        if (!$announcement instanceof MastodonAnnouncement) {
            throw new \LogicException(sprintf('Missing Mastodon retention row for account "%s".', $account));
        }
        return $announcement;
    }

    private function assertAccount(string $account): void
    {
        if (!in_array($account, self::ACCOUNTS, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown Mastodon account "%s".', $account));
        }
    }
}
