<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180902012433 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_episode CHANGE crawler_output crawler_output LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE na_episode ADD special TINYINT(1) DEFAULT NULL AFTER author');
        $this->addSql('ALTER TABLE na_episode ADD chat_messages TINYINT(1) DEFAULT NULL AFTER special');
        $this->addSql('ALTER TABLE na_episode ADD transcript TINYINT(1) DEFAULT NULL AFTER chat_messages');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_episode CHANGE crawler_output crawler_output LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE na_episode DROP special');
        $this->addSql('ALTER TABLE na_episode DROP chat_messages');
        $this->addSql('ALTER TABLE na_episode DROP transcript');
    }
}
