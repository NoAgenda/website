<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022010101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track crawler-owned Mastodon announcements for selective retention';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE na_mastodon_announcement (
                id INT AUTO_INCREMENT NOT NULL,
                account VARCHAR(16) NOT NULL,
                current_status_id VARCHAR(64) DEFAULT NULL,
                pending_deletion_ids JSON NOT NULL,
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX uniq_mastodon_announcement_account (account),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql("INSERT INTO na_mastodon_announcement (account, pending_deletion_ids, updated_at) VALUES ('primary', '[]', NOW()), ('secondary', '[]', NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE na_mastodon_announcement');
    }
}
