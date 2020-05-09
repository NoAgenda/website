<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200509013256 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Shownotes';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_episode ADD shownotes_uri LONGTEXT DEFAULT NULL');

        $statement = $this->connection->query('SELECT id, shownotes FROM na_episode');

        foreach ($statement->fetchAll() as $episode) {
            if (!$episode['shownotes']) { continue; }

            $shownotes = json_decode($episode['shownotes'], true);

            if (!isset($shownotes['url'])) { continue; }

            $this->addSql('UPDATE na_episode SET shownotes_uri = "' . $shownotes['url'] . '" WHERE id = ' . $episode['id']);
        }

        $this->addSql('ALTER TABLE na_episode DROP shownotes');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE na_episode ADD shownotes JSON DEFAULT NULL, DROP shownotes_uri');
    }
}
