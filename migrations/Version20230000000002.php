<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230000000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update old media paths';
    }

    public function up(Schema $schema): void
    {
        $statement = $this->connection->executeQuery('SELECT id, cover_uri FROM na_episode WHERE cover_uri LIKE "http://adam.curry.com%"');

        foreach ($statement->fetchAllAssociative() as $episode) {
            $this->addSql('UPDATE na_episode SET cover_uri = :coverUri WHERE id = :id', [
                'coverUri' => str_replace('http://adam.curry.com', 'https://noagendaassets.com', $episode['cover_uri']),
                'id' => $episode['id'],
            ]);
        }

        $statement = $this->connection->executeQuery('SELECT id, recording_uri FROM na_episode WHERE recording_uri LIKE "http://mp3s.nashownotes.com%"');

        foreach ($statement->fetchAllAssociative() as $episode) {
            $this->addSql('UPDATE na_episode SET recording_uri = :recordingUri WHERE id = :id', [
                'recordingUri' => str_replace('http://', 'https://', $episode['recording_uri']),
                'id' => $episode['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
