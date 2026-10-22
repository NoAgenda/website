<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MastodonAnnouncementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MastodonAnnouncementRepository::class)]
#[ORM\Table(name: 'na_mastodon_announcement')]
#[ORM\UniqueConstraint(name: 'uniq_mastodon_announcement_account', columns: ['account'])]
class MastodonAnnouncement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    private string $account;

    #[ORM\Column(name: 'current_status_id', length: 64, nullable: true)]
    private ?string $currentStatusId = null;

    #[ORM\Column(name: 'pending_deletion_ids', type: Types::JSON)]
    private array $pendingDeletionIds = [];

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $account)
    {
        $this->account = $account;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getCurrentStatusId(): ?string
    {
        return $this->currentStatusId;
    }

    public function getPendingDeletionIds(): array
    {
        return $this->pendingDeletionIds;
    }

    public function setCurrentStatusId(string $statusId): self
    {
        if (!preg_match('/\A[0-9]{1,64}\z/', $statusId)) {
            throw new \InvalidArgumentException('Mastodon status IDs must contain only 1-64 decimal digits.');
        }

        if ($this->currentStatusId && $this->currentStatusId !== $statusId && !in_array($this->currentStatusId, $this->pendingDeletionIds, true)) {
            $this->pendingDeletionIds[] = $this->currentStatusId;
        }

        $this->currentStatusId = $statusId;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function clearPendingDeletion(string $statusId): self
    {
        $this->pendingDeletionIds = array_values(array_filter(
            $this->pendingDeletionIds,
            static fn (string $pendingId): bool => $pendingId !== $statusId,
        ));
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
