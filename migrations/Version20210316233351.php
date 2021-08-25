<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210316233351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Octopod integration';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE na_episode
                ADD guid LONGTEXT DEFAULT NULL,
                ADD title VARCHAR(1024) DEFAULT NULL,
                ADD link LONGTEXT DEFAULT NULL,
                ADD description LONGTEXT DEFAULT NULL,
                ADD explicit TINYINT(1) NOT NULL,
                ADD enclosure_length INT DEFAULT NULL,
                ADD enclosure_type VARCHAR(255) DEFAULT NULL,
                ADD chapters_url LONGTEXT DEFAULT NULL,
                ADD chapters_type VARCHAR(255) DEFAULT NULL,
                ADD transcript_type VARCHAR(255) DEFAULT NULL,
                DROP crawler_output,
                CHANGE author author VARCHAR(1024) DEFAULT NULL,
                CHANGE cover_uri image LONGTEXT DEFAULT NULL,
                CHANGE published_at published_at DATETIME DEFAULT NULL,
                CHANGE recording_uri enclosure_url LONGTEXT NOT NULL,
                CHANGE transcript_uri transcript_url LONGTEXT DEFAULT NULL,
                CHANGE shownotes_uri shownotes_url LONGTEXT DEFAULT NULL
            ;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE na_episode
                ADD crawler_output LONGTEXT COMMENT \'(DC2Type:array)\',
                DROP guid,
                DROP title,
                DROP link,
                DROP description,
                DROP explicit,
                DROP enclosure_length,
                DROP enclosure_type,
                DROP chapters_url,
                DROP chapters_type,
                DROP transcript_type,
                CHANGE author author VARCHAR(255),
                CHANGE image cover_uri  LONGTEXT DEFAULT NULL,
                CHANGE published_at published_at DATE NOT NULL,
                CHANGE enclosure_url recording_uri  LONGTEXT NOT NULL,
                CHANGE transcript_uri transcript_url LONGTEXT DEFAULT NULL,
                CHANGE shownotes_uri shownotes_url LONGTEXT DEFAULT NULL
            ;
        ');
    }
}
