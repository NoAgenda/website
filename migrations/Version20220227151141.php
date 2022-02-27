<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220227151141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Optimize tiny string fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_bat_signal CHANGE code code VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE na_episode CHANGE code code VARCHAR(16) NOT NULL, CHANGE transcript_type transcript_type VARCHAR(16) DEFAULT NULL');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_bat_signal CHANGE code code VARCHAR(15) NOT NULL');
        $this->addSql('ALTER TABLE na_episode CHANGE code code VARCHAR(15) NOT NULL, CHANGE transcript_type transcript_type TINYTEXT NOT NULL');
    }
}
