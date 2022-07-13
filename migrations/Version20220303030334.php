<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;
use function Symfony\Component\String\u;

final class Version20220303030334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor users (update feedback items)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_feedback_item ADD creator_id INT DEFAULT NULL');

        $statement = $this->connection->executeQuery('SELECT * FROM na_episode_chapter_draft');

        foreach ($statement->fetchAllAssociative() as $chapterDraft) {
            if (null === $chapterDraft['creator_id']) {
                dump($chapterDraft);
                die;
            }

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

