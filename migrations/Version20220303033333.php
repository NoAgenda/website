<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220303033333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop transcript type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_episode DROP transcript_type');
    }

    public function down(Schema $schema): void
    {
        // Not enough investment was made to make a rollback possible
    }
}
