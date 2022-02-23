<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220203223434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Improve transcript tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_episode ADD transcript_type TINYTEXT NOT NULL');

        $this->addSql('UPDATE na_episode SET transcript = 1, transcript_type = "json" WHERE beta_transcript = 1;');
        $this->addSql('UPDATE na_episode SET transcript_type = "srt" WHERE transcript = 1;');

        $this->addSql('ALTER TABLE na_episode DROP beta_transcript');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_episode ADD beta_transcript TINYINT(1) NOT NULL');

        $this->addSql('UPDATE na_episode SET transcript = 0, beta_transcript = 1 WHERE transcript_type = "json";');
        $this->addSql('UPDATE na_episode SET WHERE transcript = 1 HERE transcript_type = "srt";');

        $this->addSql('ALTER TABLE na_episode DROP transcript_type');
    }
}
