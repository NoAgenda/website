<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220222202202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration Reset';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE na_bat_signal (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(16) NOT NULL, deployed_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_episode (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(16) NOT NULL, name VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, cover TINYINT(1) NOT NULL, special TINYINT(1) NOT NULL, chat_messages TINYINT(1) NOT NULL, transcript TINYINT(1) NOT NULL, transcript_type VARCHAR(16) DEFAULT NULL, published_at DATE NOT NULL, cover_uri LONGTEXT DEFAULT NULL, recording_uri LONGTEXT NOT NULL, shownotes_uri LONGTEXT DEFAULT NULL, transcript_uri LONGTEXT DEFAULT NULL, chat_notice LONGTEXT DEFAULT NULL, duration INT DEFAULT NULL, recorded_at DATETIME DEFAULT NULL, crawler_output LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_episode_chapter (id INT AUTO_INCREMENT NOT NULL, episode_id INT NOT NULL, creator_id INT DEFAULT NULL, creator_token_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', name VARCHAR(1024) DEFAULT NULL, description LONGTEXT DEFAULT NULL, starts_at INT NOT NULL, duration INT DEFAULT NULL, INDEX IDX_8F56643D362B62A0 (episode_id), INDEX IDX_8F56643D61220EA6 (creator_id), INDEX IDX_8F56643D1638C025 (creator_token_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_episode_chapter_draft (id INT AUTO_INCREMENT NOT NULL, episode_id INT NOT NULL, chapter_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, creator_token_id INT DEFAULT NULL, feedback_item_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', name VARCHAR(1024) DEFAULT NULL, description LONGTEXT DEFAULT NULL, starts_at INT NOT NULL, duration INT DEFAULT NULL, INDEX IDX_1C1DB335362B62A0 (episode_id), INDEX IDX_1C1DB335579F4768 (chapter_id), INDEX IDX_1C1DB33561220EA6 (creator_id), INDEX IDX_1C1DB3351638C025 (creator_token_id), UNIQUE INDEX UNIQ_1C1DB335833672FD (feedback_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_feedback_item (id INT AUTO_INCREMENT NOT NULL, entity_name VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, accepted TINYINT(1) NOT NULL, rejected TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_feedback_vote (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, creator_id INT DEFAULT NULL, creator_token_id INT DEFAULT NULL, supported TINYINT(1) NOT NULL, rejected TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_EFE4F376126F525E (item_id), INDEX IDX_EFE4F37661220EA6 (creator_id), INDEX IDX_EFE4F3761638C025 (creator_token_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_network_site (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, icon VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, uri VARCHAR(255) NOT NULL, display_uri VARCHAR(255) NOT NULL, priority INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, salt VARCHAR(255) DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', hidden TINYINT(1) NOT NULL, activation_token VARCHAR(255) DEFAULT NULL, activation_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_93D96576F85E0677 (username), UNIQUE INDEX UNIQ_93D96576E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_user_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(255) NOT NULL, ip_addresses LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_video (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, published_at DATETIME NOT NULL, youtube_id VARCHAR(32) NOT NULL, youtube_etag VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
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
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_episode_chapter DROP FOREIGN KEY FK_8F56643D362B62A0');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB335362B62A0');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB335579F4768');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB335833672FD');
        $this->addSql('ALTER TABLE na_feedback_vote DROP FOREIGN KEY FK_EFE4F376126F525E');
        $this->addSql('ALTER TABLE na_episode_chapter DROP FOREIGN KEY FK_8F56643D61220EA6');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB33561220EA6');
        $this->addSql('ALTER TABLE na_feedback_vote DROP FOREIGN KEY FK_EFE4F37661220EA6');
        $this->addSql('ALTER TABLE na_episode_chapter DROP FOREIGN KEY FK_8F56643D1638C025');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB3351638C025');
        $this->addSql('ALTER TABLE na_feedback_vote DROP FOREIGN KEY FK_EFE4F3761638C025');
        $this->addSql('DROP TABLE na_bat_signal');
        $this->addSql('DROP TABLE na_episode');
        $this->addSql('DROP TABLE na_episode_chapter');
        $this->addSql('DROP TABLE na_episode_chapter_draft');
        $this->addSql('DROP TABLE na_feedback_item');
        $this->addSql('DROP TABLE na_feedback_vote');
        $this->addSql('DROP TABLE na_network_site');
        $this->addSql('DROP TABLE na_user');
        $this->addSql('DROP TABLE na_user_token');
        $this->addSql('DROP TABLE na_video');
    }
}
