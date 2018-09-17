<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180917095600 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE na_episode_part_correction (id INT AUTO_INCREMENT NOT NULL, part_id INT NOT NULL, result_id INT DEFAULT NULL, creator_id INT NOT NULL, action VARCHAR(15) DEFAULT NULL, position VARCHAR(15) DEFAULT NULL, name VARCHAR(1023) DEFAULT NULL, description LONGTEXT DEFAULT NULL, handled TINYINT(1) NOT NULL, starts_at INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_11C123BD4CE34BEC (part_id), INDEX IDX_11C123BD7A7B643 (result_id), INDEX IDX_11C123BD61220EA6 (creator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE na_episode_part_correction ADD CONSTRAINT FK_11C123BD4CE34BEC FOREIGN KEY (part_id) REFERENCES na_episode_part (id)');
        $this->addSql('ALTER TABLE na_episode_part_correction ADD CONSTRAINT FK_11C123BD7A7B643 FOREIGN KEY (result_id) REFERENCES na_episode_part (id)');
        $this->addSql('ALTER TABLE na_episode_part_correction ADD CONSTRAINT FK_11C123BD61220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id)');

        $this->addSql('CREATE TABLE na_episode_part_correction_vote (id INT AUTO_INCREMENT NOT NULL, correction_id INT NOT NULL, creator_id INT NOT NULL, supported TINYINT(1) NOT NULL, rejected TINYINT(1) NOT NULL, questioned TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9E51E66D94AE086B (correction_id), INDEX IDX_9E51E66D61220EA6 (creator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE na_episode_part_correction_vote ADD CONSTRAINT FK_9E51E66D94AE086B FOREIGN KEY (correction_id) REFERENCES na_episode_part_correction (id)');
        $this->addSql('ALTER TABLE na_episode_part_correction_vote ADD CONSTRAINT FK_9E51E66D61220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id)');

        $this->addSql('ALTER TABLE na_chat_message CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql('ALTER TABLE na_episode_part ADD enabled TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE na_episode_part SET enabled = 1');
        $this->addSql('ALTER TABLE na_episode_part CHANGE enabled enabled TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE na_episode_part ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE na_episode_part SET created_at = "2018-09-01 00:00:00"');
        $this->addSql('ALTER TABLE na_episode_part CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_episode_part_correction_vote DROP FOREIGN KEY FK_9E51E66D94AE086B');
        $this->addSql('DROP TABLE na_episode_part_correction');

        $this->addSql('DROP TABLE na_episode_part_correction_vote');

        $this->addSql('ALTER TABLE na_chat_message CHANGE created_at created_at DATETIME NOT NULL');

        $this->addSql('ALTER TABLE na_episode_part DROP enabled');
        $this->addSql('ALTER TABLE na_episode_part DROP created_at');
    }
}
