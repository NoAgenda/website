<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220303033301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last modified dates for caching';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_episode ADD last_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE na_episode SET last_modified_at = NOW()');
        $this->addSql('ALTER TABLE na_episode CHANGE last_modified_at last_modified_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE na_video ADD last_modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE na_video SET last_modified_at = NOW()');
        $this->addSql('ALTER TABLE na_video CHANGE last_modified_at last_modified_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // Not enough investment was made to make a rollback possible
    }
}
