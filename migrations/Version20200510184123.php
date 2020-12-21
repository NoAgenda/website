<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200510184123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Episode chapters';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE na_episode_chapter (id INT AUTO_INCREMENT NOT NULL, episode_id INT NOT NULL, creator_id INT DEFAULT NULL, creator_token_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', name VARCHAR(1024) DEFAULT NULL, description LONGTEXT DEFAULT NULL, starts_at INT NOT NULL, duration INT DEFAULT NULL, INDEX IDX_8F56643D362B62A0 (episode_id), INDEX IDX_8F56643D61220EA6 (creator_id), INDEX IDX_8F56643D1638C025 (creator_token_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_episode_chapter_draft (id INT AUTO_INCREMENT NOT NULL, episode_id INT NOT NULL, chapter_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, creator_token_id INT DEFAULT NULL, feedback_item_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', name VARCHAR(1024) DEFAULT NULL, description LONGTEXT DEFAULT NULL, starts_at INT NOT NULL, duration INT DEFAULT NULL, INDEX IDX_1C1DB335362B62A0 (episode_id), INDEX IDX_1C1DB335579F4768 (chapter_id), INDEX IDX_1C1DB33561220EA6 (creator_id), INDEX IDX_1C1DB3351638C025 (creator_token_id), UNIQUE INDEX UNIQ_1C1DB335833672FD (feedback_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_feedback_item (id INT AUTO_INCREMENT NOT NULL, entity_name VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, accepted TINYINT(1) NOT NULL, rejected TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_feedback_vote (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, creator_id INT DEFAULT NULL, creator_token_id INT DEFAULT NULL, supported TINYINT(1) NOT NULL, rejected TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_EFE4F376126F525E (item_id), INDEX IDX_EFE4F37661220EA6 (creator_id), INDEX IDX_EFE4F3761638C025 (creator_token_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE na_episode_chapter ADD CONSTRAINT FK_8F56643D362B62A0 FOREIGN KEY (episode_id) REFERENCES na_episode (id)');
        $this->addSql('ALTER TABLE na_episode_chapter ADD CONSTRAINT FK_8F56643D61220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id)');
        $this->addSql('ALTER TABLE na_episode_chapter ADD CONSTRAINT FK_8F56643D1638C025 FOREIGN KEY (creator_token_id) REFERENCES na_user_token (id)');
        $this->addSql('ALTER TABLE na_episode_chapter_draft ADD CONSTRAINT FK_1C1DB335362B62A0 FOREIGN KEY (episode_id) REFERENCES na_episode (id)');
        $this->addSql('ALTER TABLE na_episode_chapter_draft ADD CONSTRAINT FK_1C1DB335579F4768 FOREIGN KEY (chapter_id) REFERENCES na_episode_chapter (id)');
        $this->addSql('ALTER TABLE na_episode_chapter_draft ADD CONSTRAINT FK_1C1DB33561220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id)');
        $this->addSql('ALTER TABLE na_episode_chapter_draft ADD CONSTRAINT FK_1C1DB3351638C025 FOREIGN KEY (creator_token_id) REFERENCES na_user_token (id)');
        $this->addSql('ALTER TABLE na_episode_chapter_draft ADD CONSTRAINT FK_1C1DB335833672FD FOREIGN KEY (feedback_item_id) REFERENCES na_feedback_item (id)');
        $this->addSql('ALTER TABLE na_feedback_vote ADD CONSTRAINT FK_EFE4F376126F525E FOREIGN KEY (item_id) REFERENCES na_feedback_item (id)');
        $this->addSql('ALTER TABLE na_feedback_vote ADD CONSTRAINT FK_EFE4F37661220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id)');
        $this->addSql('ALTER TABLE na_feedback_vote ADD CONSTRAINT FK_EFE4F3761638C025 FOREIGN KEY (creator_token_id) REFERENCES na_user_token (id)');

        $this->addSql('
            INSERT INTO na_episode_chapter (id, episode_id, creator_id, created_at, name, description, starts_at, duration)
            SELECT id, episode_id, creator_id, created_at, name, description, starts_at, duration FROM na_episode_part
        ');

        $this->addSql('ALTER TABLE na_episode_part_correction DROP FOREIGN KEY FK_11C123BD4CE34BEC');
        $this->addSql('ALTER TABLE na_episode_part_correction DROP FOREIGN KEY FK_11C123BD7A7B643');
        $this->addSql('ALTER TABLE na_episode_part_correction_vote DROP FOREIGN KEY FK_9E51E66D94AE086B');
        $this->addSql('DROP TABLE na_episode_part');
        $this->addSql('DROP TABLE na_episode_part_correction');
        $this->addSql('DROP TABLE na_episode_part_correction_vote');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE na_episode_part (id INT AUTO_INCREMENT NOT NULL, episode_id INT NOT NULL, creator_id INT NOT NULL, name VARCHAR(1023), description LONGTEXT, starts_at INT NOT NULL, duration INT DEFAULT NULL, enabled TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_11BAF8E3362B62A0 (episode_id), INDEX IDX_11BAF8E361220EA6 (creator_id), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE na_episode_part_correction (id INT AUTO_INCREMENT NOT NULL, part_id INT NOT NULL, result_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, creator_token_id INT DEFAULT NULL, action VARCHAR(15), position VARCHAR(15), name VARCHAR(1023), description LONGTEXT, handled TINYINT(1) NOT NULL, starts_at INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_11C123BD1638C025 (creator_token_id), INDEX IDX_11C123BD4CE34BEC (part_id), INDEX IDX_11C123BD61220EA6 (creator_id), INDEX IDX_11C123BD7A7B643 (result_id), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE na_episode_part_correction_vote (id INT AUTO_INCREMENT NOT NULL, correction_id INT NOT NULL, creator_id INT DEFAULT NULL, creator_token_id INT DEFAULT NULL, supported TINYINT(1) NOT NULL, rejected TINYINT(1) NOT NULL, questioned TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9E51E66D1638C025 (creator_token_id), INDEX IDX_9E51E66D61220EA6 (creator_id), INDEX IDX_9E51E66D94AE086B (correction_id), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE na_episode_part ADD CONSTRAINT FK_11BAF8E3362B62A0 FOREIGN KEY (episode_id) REFERENCES na_episode (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE na_episode_part ADD CONSTRAINT FK_11BAF8E361220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE na_episode_part_correction ADD CONSTRAINT FK_11C123BD1638C025 FOREIGN KEY (creator_token_id) REFERENCES na_user_token (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE na_episode_part_correction ADD CONSTRAINT FK_11C123BD4CE34BEC FOREIGN KEY (part_id) REFERENCES na_episode_part (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE na_episode_part_correction ADD CONSTRAINT FK_11C123BD61220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE na_episode_part_correction ADD CONSTRAINT FK_11C123BD7A7B643 FOREIGN KEY (result_id) REFERENCES na_episode_part (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE na_episode_part_correction_vote ADD CONSTRAINT FK_9E51E66D1638C025 FOREIGN KEY (creator_token_id) REFERENCES na_user_token (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE na_episode_part_correction_vote ADD CONSTRAINT FK_9E51E66D61220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE na_episode_part_correction_vote ADD CONSTRAINT FK_9E51E66D94AE086B FOREIGN KEY (correction_id) REFERENCES na_episode_part_correction (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB335579F4768');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB335833672FD');
        $this->addSql('ALTER TABLE na_feedback_vote DROP FOREIGN KEY FK_EFE4F376126F525E');
        $this->addSql('DROP TABLE na_episode_chapter');
        $this->addSql('DROP TABLE na_episode_chapter_draft');
        $this->addSql('DROP TABLE na_feedback_item');
        $this->addSql('DROP TABLE na_feedback_vote');
    }
}
