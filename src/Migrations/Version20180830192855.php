<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180830192855 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE na_bat_signal (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(15) NOT NULL, processed TINYINT(1) NOT NULL, deployed_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_chat_message (id INT AUTO_INCREMENT NOT NULL, episode_id INT NOT NULL, username VARCHAR(255) NOT NULL, contents LONGTEXT NOT NULL, posted_at INT NOT NULL, source INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_C6D0E813362B62A0 (episode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_episode (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(15) NOT NULL, name VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, published_at DATE NOT NULL, cover_uri LONGTEXT NOT NULL, recording_uri LONGTEXT NOT NULL, duration INT DEFAULT NULL, recorded_at DATETIME DEFAULT NULL, crawler_output LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_episode_part (id INT AUTO_INCREMENT NOT NULL, episode_id INT NOT NULL, creator_id INT NOT NULL, name VARCHAR(1023) NOT NULL, description LONGTEXT DEFAULT NULL, starts_at INT NOT NULL, duration INT DEFAULT NULL, INDEX IDX_11BAF8E3362B62A0 (episode_id), INDEX IDX_11BAF8E361220EA6 (creator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_transcript_line (id INT AUTO_INCREMENT NOT NULL, episode_id INT NOT NULL, timestamp INT NOT NULL, duration INT NOT NULL, text LONGTEXT NOT NULL, crawler_output LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_C4032523362B62A0 (episode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE na_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, hidden TINYINT(1) NOT NULL, password VARCHAR(255) DEFAULT NULL, salt VARCHAR(255) DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', UNIQUE INDEX UNIQ_93D96576F85E0677 (username), UNIQUE INDEX UNIQ_93D96576E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE na_chat_message ADD CONSTRAINT FK_C6D0E813362B62A0 FOREIGN KEY (episode_id) REFERENCES na_episode (id)');
        $this->addSql('ALTER TABLE na_episode_part ADD CONSTRAINT FK_11BAF8E3362B62A0 FOREIGN KEY (episode_id) REFERENCES na_episode (id)');
        $this->addSql('ALTER TABLE na_episode_part ADD CONSTRAINT FK_11BAF8E361220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id)');
        $this->addSql('ALTER TABLE na_transcript_line ADD CONSTRAINT FK_C4032523362B62A0 FOREIGN KEY (episode_id) REFERENCES na_episode (id)');

        $this->addSql('INSERT INTO na_user (id, username, email, hidden, password, salt, roles) VALUES (1, \'Woodstock\', \'admin@noagendaexperience.com\', 0, \'$2y$13$6QO8DquJHGp1ny08x9mECukBfspU8jRsWgprwipOlYb2DRjd7OFta\', \'a8af01f735649f8685d986753e470d78\', \'a:2:{i:0;s:9:"ROLE_USER";i:1;s:16:"ROLE_SUPER_ADMIN";}\')');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_chat_message DROP FOREIGN KEY FK_C6D0E813362B62A0');
        $this->addSql('ALTER TABLE na_episode_part DROP FOREIGN KEY FK_11BAF8E3362B62A0');
        $this->addSql('ALTER TABLE na_transcript_line DROP FOREIGN KEY FK_C4032523362B62A0');
        $this->addSql('ALTER TABLE na_episode_part DROP FOREIGN KEY FK_11BAF8E361220EA6');
        $this->addSql('DROP TABLE na_bat_signal');
        $this->addSql('DROP TABLE na_chat_message');
        $this->addSql('DROP TABLE na_episode');
        $this->addSql('DROP TABLE na_episode_part');
        $this->addSql('DROP TABLE na_transcript_line');
        $this->addSql('DROP TABLE na_user');
    }
}
