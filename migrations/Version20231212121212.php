<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231212121212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove user-contributed entities and refactor security';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_93D96576D0494586 ON na_user');
        $this->addSql('ALTER TABLE na_user DROP user_identifier');
        $this->addSql('ALTER TABLE na_user ADD user_identifier VARCHAR(255) NOT NULL, ADD password VARCHAR(255) NOT NULL, ADD roles JSON NOT NULL');

        $statement = $this->connection->executeQuery('SELECT * FROM na_user_account');

        foreach ($statement->fetchAllAssociative() as $legacyAccount) {
            $this->addSql('UPDATE na_user SET user_identifier = ?, password = ?, roles = ? WHERE id = ?', [
                $legacyAccount['username'],
                $legacyAccount['password'],
                json_encode(unserialize($legacyAccount['roles'])),
                $legacyAccount['user_id'],
            ]);
        }

        $this->addSql('ALTER TABLE na_episode_chapter DROP FOREIGN KEY FK_8F56643D362B62A0');
        $this->addSql('ALTER TABLE na_episode_chapter DROP FOREIGN KEY FK_8F56643D61220EA6');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB335362B62A0');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB335579F4768');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB33561220EA6');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB335833672FD');
        $this->addSql('ALTER TABLE na_feedback_item DROP FOREIGN KEY FK_AAEF530C61220EA6');
        $this->addSql('ALTER TABLE na_feedback_vote DROP FOREIGN KEY FK_EFE4F376126F525E');
        $this->addSql('ALTER TABLE na_feedback_vote DROP FOREIGN KEY FK_EFE4F37661220EA6');
        $this->addSql('ALTER TABLE na_user_account DROP FOREIGN KEY FK_19585CABA76ED395');
        $this->addSql('ALTER TABLE na_user_token DROP FOREIGN KEY FK_FDAFABFCA76ED395');
        $this->addSql('DROP TABLE na_episode_chapter');
        $this->addSql('DROP TABLE na_episode_chapter_draft');
        $this->addSql('DROP TABLE na_feedback_item');
        $this->addSql('DROP TABLE na_feedback_vote');
        $this->addSql('DROP TABLE na_user_account');
        $this->addSql('DROP TABLE na_user_token');
        $this->addSql('ALTER TABLE na_user DROP FOREIGN KEY FK_93D9657613B3DB11');
        $this->addSql('DROP INDEX IDX_93D9657613B3DB11 ON na_user');
        $this->addSql('ALTER TABLE na_user DROP master_id, DROP banned, DROP hidden, DROP reviewed, DROP needs_review');
    }

    public function down(Schema $schema): void
    {
        // Not enough investment was made to make a rollback possible
        // Go to https://www.dudenamedben.blog/donate to fix this terrible issue
    }
}
