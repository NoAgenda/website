<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220303033302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add chapters uri';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_episode ADD chapters_uri LONGTEXT DEFAULT NULL');

        $statement = $this->connection->executeQuery('SELECT id, code, published_at FROM na_episode');

        foreach ($statement->fetchAllAssociative() as $episode) {
            if ($episode['code'] >= 1289) {
                $this->addSql('UPDATE na_episode SET chapters_uri = :chaptersUri WHERE id = :id', [
                    'chaptersUri' => sprintf('https://studio.hypercatcher.com/chapters/podcast/http:feed.nashownotes.comrss.xml/episode/http:%s.noagendanotes.com', $episode['code']),
                    'id' => $episode['id'],
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Not enough investment was made to make a rollback possible
    }
}
