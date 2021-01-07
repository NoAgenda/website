<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210107045256 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Official transcripts';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_episode ADD cover TINYINT(1) DEFAULT NULL, ADD beta_transcript TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE na_episode SET special = 0 WHERE special IS NULL');
        $this->addSql('UPDATE na_episode SET chat_messages = 0 WHERE chat_messages IS NULL');
        $this->addSql('UPDATE na_episode SET transcript = 0 WHERE transcript IS NULL');
        $this->addSql('UPDATE na_episode SET cover = 0');
        $this->addSql('UPDATE na_episode SET cover = 1 WHERE cover_uri IS NOT NULL');
        $this->addSql('UPDATE na_episode SET beta_transcript = 0');
        $this->addSql('UPDATE na_episode SET transcript = 0, beta_transcript = 1, transcript_uri = NULL WHERE transcript = 1');
        $this->addSql('ALTER TABLE na_episode CHANGE cover cover TINYINT(1) NOT NULL, CHANGE special special TINYINT(1) NOT NULL, CHANGE chat_messages chat_messages TINYINT(1) NOT NULL, CHANGE transcript transcript TINYINT(1) NOT NULL, CHANGE beta_transcript beta_transcript TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE na_episode SET transcript = 0');
        $this->addSql('UPDATE na_episode SET transcript = 1 WHERE beta_transcript = 1');
        $this->addSql('ALTER TABLE na_episode DROP cover, DROP beta_transcript');
        $this->addSql('ALTER TABLE na_episode CHANGE special special TINYINT(1) DEFAULT NULL, CHANGE chat_messages chat_messages TINYINT(1) DEFAULT NULL, CHANGE transcript transcript TINYINT(1) DEFAULT NULL');
    }
}
