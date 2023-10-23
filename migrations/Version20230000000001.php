<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230000000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Podcasting 2.0 chapters metadata';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_episode ADD chapters_path LONGTEXT DEFAULT NULL');

        $statement = $this->connection->executeQuery('SELECT id, code, published_at FROM na_episode');

        foreach ($statement->fetchAllAssociative() as $episode) {
            if ($episode['code'] >= 1289) {
                $this->addSql('UPDATE na_episode SET chapters_uri = :chaptersUri WHERE id = :id', [
                    'chaptersUri' => sprintf('https://chapters.hypercatcher.com/http:feed.nashownotes.comrss.xml/http:%s.noagendanotes.com', $episode['code']),
                    'id' => $episode['id'],
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE na_episode DROP chapters_path');
    }
}
