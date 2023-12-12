<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231212121233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean up episode data';
    }

    public function up(Schema $schema): void
    {
        $statement = $this->connection->executeQuery('SELECT id, crawler_output FROM na_episode');

        $this->addSql('ALTER TABLE na_episode DROP crawler_output');
        $this->addSql('ALTER TABLE na_episode ADD crawler_output JSON DEFAULT NULL');

        foreach ($statement->fetchAllAssociative() as $episode) {
            $this->addSql('UPDATE na_episode SET crawler_output = ? WHERE id = ?', [
                json_encode(unserialize($episode['crawler_output'] ?? 'N;')),
                $episode['id'],
            ]);
        }

        $this->addSql('ALTER TABLE na_episode DROP chat_notice, DROP recorded_at, DROP chat_archive_path, DROP recording_time_matrix');
    }

    public function down(Schema $schema): void
    {
        // Not enough investment was made to make a rollback possible
        // Go to https://www.dudenamedben.blog/donate to fix this terrible issue
    }
}
