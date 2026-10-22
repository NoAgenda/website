<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\MastodonAnnouncement;
use Doctrine\DBAL\DriverManager;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MastodonAnnouncementRepositoryIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->resetRows();
    }

    protected function tearDown(): void
    {
        $this->resetRows();
        $this->entityManager->clear();
        parent::tearDown();
    }

    public function testCleanupRefreshesStaleSnapshotsForBothAccounts(): void
    {
        $secondEntityManager = $this->newEntityManager();
        $firstRepository = $this->entityManager->getRepository(MastodonAnnouncement::class);
        $secondRepository = $secondEntityManager->getRepository(MastodonAnnouncement::class);

        foreach (['primary', 'secondary'] as $account) {
            $secondRepository->recordPublishedStatus($account, '100');
            $staleAnnouncement = $firstRepository->findOneBy(['account' => $account]);
            self::assertNotNull($staleAnnouncement);

            $secondRepository->recordPublishedStatus($account, '200');
            $secondRepository->recordPublishedStatus($account, '300');
            $firstRepository->clearPendingDeletion($account, '100');

            $row = $this->entityManager->getConnection()->fetchAssociative(
                'SELECT current_status_id, pending_deletion_ids FROM na_mastodon_announcement WHERE account = ?',
                [$account],
            );
            self::assertSame('300', $row['current_status_id']);
            self::assertSame(['200'], json_decode($row['pending_deletion_ids'], true));

            $firstRepository->clearPendingDeletion($account, '200');
            $row = $this->entityManager->getConnection()->fetchAssociative(
                'SELECT current_status_id, pending_deletion_ids FROM na_mastodon_announcement WHERE account = ?',
                [$account],
            );
            self::assertSame('300', $row['current_status_id']);
            self::assertSame([], json_decode($row['pending_deletion_ids'], true));
        }

        $secondEntityManager->close();
    }

    public function testConcurrentProcessesSerializeAccountPublication(): void
    {
        $directory = sys_get_temp_dir().'/mastodon-lock-'.bin2hex(random_bytes(6));
        mkdir($directory);
        $first = $this->startLockWorker($directory.'/first');
        $this->waitForFile($directory.'/first-entered');
        $second = $this->startLockWorker($directory.'/second');
        usleep(200000);
        self::assertFileDoesNotExist($directory.'/second-entered');
        self::assertSame(0, proc_close($first));
        $this->waitForFile($directory.'/second-entered');
        self::assertSame(0, proc_close($second));
        self::assertFileExists($directory.'/first-entered');
        self::assertFileExists($directory.'/second-entered');
        unlink($directory.'/first-entered');
        unlink($directory.'/second-entered');
        rmdir($directory);
    }

    private function newEntityManager(): EntityManagerInterface
    {
        return new EntityManager(
            DriverManager::getConnection($this->entityManager->getConnection()->getParams()),
            $this->entityManager->getConfiguration(),
            new EventManager(),
        );
    }

    private function resetRows(): void
    {
        if (!isset($this->entityManager)) {
            return;
        }
        $repository = $this->entityManager->getRepository(MastodonAnnouncement::class);
        foreach (['primary', 'secondary'] as $account) {
            $repository->withAccountLock($account, function () use ($account): void {
                $this->entityManager->getConnection()->executeStatement(
                    'INSERT INTO na_mastodon_announcement (account, pending_deletion_ids, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE current_status_id = NULL, pending_deletion_ids = VALUES(pending_deletion_ids), updated_at = NOW()',
                    [$account, '[]'],
                );
            });
        }
        $this->entityManager->clear();
    }

    private function startLockWorker(string $name): mixed
    {
        $command = [PHP_BINARY, __DIR__.'/account_lock_worker.php', $name.'-entered'];
        $pipes = [];
        return proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    }

    private function waitForFile(string $path): void
    {
        for ($i = 0; $i < 50 && !file_exists($path); ++$i) {
            usleep(100000);
        }
        self::assertFileExists($path);
    }
}
