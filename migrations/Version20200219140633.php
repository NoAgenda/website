<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200219140633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE na_episode SET special = 0 WHERE special IS NULL;');
        $this->addSql('UPDATE na_episode SET chat_messages = 0 WHERE chat_messages IS NULL;');
        $this->addSql('UPDATE na_episode SET transcript = 0 WHERE transcript IS NULL;');
    }

    public function down(Schema $schema): void
    {
    }
}
