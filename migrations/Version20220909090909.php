<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220909090909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification subscriptions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE na_notification_subscription (id INT AUTO_INCREMENT NOT NULL, raw_subscription LONGTEXT NOT NULL, type VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Not enough investment was made to make a rollback possible
    }
}
