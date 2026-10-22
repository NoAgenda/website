<?php

namespace App\Tests\Entity;

use App\Entity\MastodonAnnouncement;
use PHPUnit\Framework\TestCase;

class MastodonAnnouncementTest extends TestCase
{
    public function testReplacingCurrentStatusTracksItOnceForDeletion(): void
    {
        $announcement = new MastodonAnnouncement('primary');

        $announcement->setCurrentStatusId('1')->setCurrentStatusId('2')->setCurrentStatusId('3');

        self::assertSame('3', $announcement->getCurrentStatusId());
        self::assertSame(['1', '2'], $announcement->getPendingDeletionIds());
    }

    public function testRejectsNonNumericStatusIds(): void
    {
        $announcement = new MastodonAnnouncement('primary');

        $this->expectException(\InvalidArgumentException::class);
        $announcement->setCurrentStatusId('status-id');
    }

    public function testClearingPendingStatusDoesNotClearCurrentStatus(): void
    {
        $announcement = new MastodonAnnouncement('primary');
        $announcement->setCurrentStatusId('1')->setCurrentStatusId('2');

        $announcement->clearPendingDeletion('1');

        self::assertSame('2', $announcement->getCurrentStatusId());
        self::assertSame([], $announcement->getPendingDeletionIds());
    }
}
