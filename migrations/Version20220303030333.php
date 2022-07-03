<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;
use function Symfony\Component\String\u;

final class Version20220303030333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Canonical user account fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_user ADD master_id INT DEFAULT NULL, ADD user_identifier BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', ADD banned TINYINT(1) DEFAULT NULL, ADD reviewed TINYINT(1) DEFAULT NULL, ADD needs_review TINYINT(1) DEFAULT NULL');

        $statement = $this->connection->executeQuery('SELECT * FROM na_user');

        foreach ($statement->fetchAllAssociative() as $legacyAccount) {
            $this->addSql('UPDATE na_user SET user_identifier = ?, banned = 0, reviewed = 0, needs_review = 0 WHERE id = ?', [
                Uuid::v4(),
                $legacyAccount['id'],
            ]);
        }

        $this->addSql('ALTER TABLE na_user CHANGE user_identifier user_identifier BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE banned banned TINYINT(1) NOT NULL, CHANGE reviewed reviewed TINYINT(1) NOT NULL, CHANGE needs_review needs_review TINYINT(1) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_93D96576D0494586 ON na_user (user_identifier)');
        $this->addSql('ALTER TABLE na_user ADD CONSTRAINT FK_93D9657613B3DB11 FOREIGN KEY (master_id) REFERENCES na_user (id)');
        $this->addSql('CREATE INDEX IDX_93D9657613B3DB11 ON na_user (master_id)');

        $this->addSql('CREATE TABLE na_user_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, username VARCHAR(255) NOT NULL, username_canonical VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, email_canonical VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', reset_password_token BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', reset_password_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_19585CAB92FC23A8 (username_canonical), UNIQUE INDEX UNIQ_19585CABA0D96FBF (email_canonical), UNIQUE INDEX UNIQ_19585CABA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE na_user_account ADD CONSTRAINT FK_19585CABA76ED395 FOREIGN KEY (user_id) REFERENCES na_user (id)');

        $statement = $this->connection->executeQuery('SELECT * FROM na_user');

        foreach ($statement->fetchAllAssociative() as $legacyAccount) {
            $this->addSql('INSERT INTO na_user_account (user_id, username, username_canonical, email, email_canonical, password, roles, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                $legacyAccount['id'],
                $legacyAccount['username'],
                u((new AsciiSlugger())->slug($legacyAccount['username'])->toString())->lower(),
                $legacyAccount['email'],
                u($legacyAccount['email'])->lower(),
                $legacyAccount['password'],
                $legacyAccount['roles'],
                $legacyAccount['created_at'],
            ]);
        }

        $this->addSql('DROP INDEX UNIQ_93D96576E7927C74 ON na_user');
        $this->addSql('DROP INDEX UNIQ_93D96576F85E0677 ON na_user');
        $this->addSql('ALTER TABLE na_user DROP username, DROP email, DROP password, DROP salt, DROP roles, DROP activation_token, DROP activation_token_expires_at');

        $this->addSql('ALTER TABLE na_user_token ADD user_id INT DEFAULT NULL, CHANGE token public_token VARCHAR(255) NOT NULL');

        $statement = $this->connection->executeQuery('SELECT * FROM na_user_token');

        foreach ($statement->fetchAllAssociative() as $userToken) {
            $this->addSql('INSERT INTO na_user (user_identifier, banned, hidden, reviewed, created_at) VALUES (?, 0, 0, 1, ?)', [
                Uuid::v4(),
                $userToken['created_at'],
            ]);

            $this->addSql('UPDATE na_user_token SET user_id = LAST_INSERT_ID() WHERE id = ?', [$userToken['id']]);
            $this->addSql('UPDATE na_episode_chapter SET creator_id = LAST_INSERT_ID() WHERE creator_token_id = ?', [$userToken['id']]);
            $this->addSql('UPDATE na_episode_chapter_draft SET creator_id = LAST_INSERT_ID() WHERE creator_token_id = ?', [$userToken['id']]);
            $this->addSql('UPDATE na_feedback_vote SET creator_id = LAST_INSERT_ID() WHERE creator_token_id = ?', [$userToken['id']]);
        }

        $this->addSql('ALTER TABLE na_user_token CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE na_user_token ADD CONSTRAINT FK_FDAFABFCA76ED395 FOREIGN KEY (user_id) REFERENCES na_user (id)');
        $this->addSql('CREATE INDEX IDX_FDAFABFCA76ED395 ON na_user_token (user_id)');

        $this->addSql('ALTER TABLE na_episode_chapter DROP FOREIGN KEY FK_8F56643D1638C025');
        $this->addSql('DROP INDEX IDX_8F56643D1638C025 ON na_episode_chapter');
        $this->addSql('ALTER TABLE na_episode_chapter DROP creator_token_id, CHANGE creator_id creator_id INT NOT NULL');

        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP FOREIGN KEY FK_1C1DB3351638C025');
        $this->addSql('DROP INDEX IDX_1C1DB3351638C025 ON na_episode_chapter_draft');
        $this->addSql('ALTER TABLE na_episode_chapter_draft DROP creator_token_id, CHANGE creator_id creator_id INT NOT NULL');

        $this->addSql('ALTER TABLE na_feedback_vote DROP FOREIGN KEY FK_EFE4F3761638C025');
        $this->addSql('DROP INDEX IDX_EFE4F3761638C025 ON na_feedback_vote');
        $this->addSql('ALTER TABLE na_feedback_vote DROP creator_token_id, CHANGE creator_id creator_id INT NOT NULL');

        $this->addSql('ALTER TABLE na_feedback_item ADD creator_id INT DEFAULT NULL');

        $statement = $this->connection->executeQuery('SELECT * FROM na_episode_chapter_draft');

        foreach ($statement->fetchAllAssociative() as $chapterDraft) {
            $this->addSql('UPDATE na_feedback_item SET creator_id = ? WHERE id = ?', [$chapterDraft['creator_id'], $chapterDraft['feedback_item_id']]);
        }

        $this->addSql('ALTER TABLE na_feedback_item CHANGE creator_id creator_id INT NOT NULL');
        $this->addSql('ALTER TABLE na_feedback_item ADD CONSTRAINT FK_AAEF530C61220EA6 FOREIGN KEY (creator_id) REFERENCES na_user (id)');
        $this->addSql('CREATE INDEX IDX_AAEF530C61220EA6 ON na_feedback_item (creator_id)');
    }

    public function down(Schema $schema): void
    {
        // Not enough investment was made to make a rollback possible
    }
}
//ALTER TABLE na_feedback_item RENAME INDEX fk_aaef530c61220ea6 TO IDX_AAEF530C61220EA6;

