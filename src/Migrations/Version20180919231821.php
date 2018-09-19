<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180919231821 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE na_user_token (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(255) NOT NULL, ip_addresses LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE na_episode_part_correction ADD creator_token_id INT DEFAULT NULL, CHANGE creator_id creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE na_episode_part_correction ADD CONSTRAINT FK_11C123BD1638C025 FOREIGN KEY (creator_token_id) REFERENCES na_user_token (id)');
        $this->addSql('CREATE INDEX IDX_11C123BD1638C025 ON na_episode_part_correction (creator_token_id)');

        $this->addSql('ALTER TABLE na_episode_part_correction_vote ADD creator_token_id INT DEFAULT NULL, CHANGE creator_id creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE na_episode_part_correction_vote ADD CONSTRAINT FK_9E51E66D1638C025 FOREIGN KEY (creator_token_id) REFERENCES na_user_token (id)');
        $this->addSql('CREATE INDEX IDX_9E51E66D1638C025 ON na_episode_part_correction_vote (creator_token_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_episode_part_correction DROP FOREIGN KEY FK_11C123BD1638C025');
        $this->addSql('ALTER TABLE na_episode_part_correction_vote DROP FOREIGN KEY FK_9E51E66D1638C025');

        $this->addSql('DROP TABLE na_user_token');

        $this->addSql('DROP INDEX IDX_11C123BD1638C025 ON na_episode_part_correction');
        $this->addSql('ALTER TABLE na_episode_part_correction DROP creator_token_id, CHANGE creator_id creator_id INT NOT NULL');

        $this->addSql('DROP INDEX IDX_9E51E66D1638C025 ON na_episode_part_correction_vote');
        $this->addSql('ALTER TABLE na_episode_part_correction_vote DROP creator_token_id, CHANGE creator_id creator_id INT NOT NULL');
    }
}
