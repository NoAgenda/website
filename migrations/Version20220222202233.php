<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220222202233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Scheduled file downloads and persistent file paths';
    }

    public function up(Schema $schema): void
    {
        $storagePath = $_SERVER['APP_STORAGE_PATH'];

        $this->addSql('CREATE TABLE na_file_download (id INT AUTO_INCREMENT NOT NULL, episode_id INT NOT NULL, crawler VARCHAR(255) NOT NULL, last_modified_at DATETIME NOT NULL, initialized_at DATETIME NOT NULL, INDEX IDX_B91C9A76362B62A0 (episode_id), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE na_file_download ADD CONSTRAINT FK_B91C9A76362B62A0 FOREIGN KEY (episode_id) REFERENCES na_episode (id)');
        $this->addSql('ALTER TABLE na_episode ADD published TINYINT(1) NOT NULL, ADD cover_path LONGTEXT DEFAULT NULL, ADD public_shownotes_uri LONGTEXT DEFAULT NULL, ADD shownotes_path LONGTEXT DEFAULT NULL, ADD transcript_path LONGTEXT DEFAULT NULL, ADD chat_archive_path LONGTEXT DEFAULT NULL, ADD recording_time_matrix JSON DEFAULT NULL');

        $this->addSql('UPDATE na_episode SET published = 1');

        $statement = $this->connection->executeQuery('SELECT id, code, cover, shownotes_uri, chat_messages, transcript, transcript_type FROM na_episode');

        foreach ($statement->fetchAllAssociative() as $episode) {
            $this->addSql('UPDATE na_episode SET cover_path = :coverPath, public_shownotes_uri = shownotes_uri, shownotes_uri = NULL, shownotes_path = :shownotesPath, transcript_path = :transcriptPath, chat_archive_path = :chatArchivePath WHERE id = :id', [
                'coverPath' => $episode['cover'] ? sprintf('%s/covers/%s.png', $storagePath, $episode['code']) : null,
                'shownotesPath' => $episode['shownotes_uri'] ? sprintf('%s/shownotes/%s.xml', $storagePath, $episode['code']) : null,
                'transcriptPath' => $episode['transcript'] ? sprintf('%s/transcript/%s.%s', $storagePath, $episode['code'], $episode['transcript_type']) : null,
                'chatArchivePath' => $episode['chat_messages'] ? sprintf('%s/chat_archives/%s.json', $storagePath, $episode['code']) : null,
                'id' => $episode['id'],
            ]);
        }

        $this->addSql('ALTER TABLE na_episode DROP cover, DROP chat_messages, DROP transcript');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE na_file_download');
        $this->addSql('ALTER TABLE na_episode ADD chat_messages TINYINT(1) NOT NULL, ADD transcript TINYINT(1) NOT NULL, DROP cover_path, DROP public_shownotes_uri, DROP shownotes_path, DROP transcript_path, DROP chat_archive_path, DROP recording_time_matrix');
    }
}
