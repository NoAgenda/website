<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200218210509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_bat_signal DROP processed');
        $this->addSql('ALTER TABLE na_episode ADD transcript_uri LONGTEXT DEFAULT NULL, CHANGE shownotes shownotes JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_bat_signal ADD processed TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE na_episode DROP transcript_uri, CHANGE shownotes shownotes LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }
}
