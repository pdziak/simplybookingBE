<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create event_persons table
 */
final class Version20250120000011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create event_persons table with event relationship';
    }

    public function up(Schema $schema): void
    {
        // Create event_persons table
        $this->addSql('CREATE TABLE event_persons (
            id SERIAL PRIMARY KEY,
            event_id INT NOT NULL,
            person_fullname VARCHAR(255) NOT NULL
        )');

        // Add foreign key constraint to events table
        $this->addSql('ALTER TABLE event_persons ADD CONSTRAINT FK_5387574A71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');

        // Create index on event_id for better query performance
        $this->addSql('CREATE INDEX IDX_5387574A71F7E88B ON event_persons (event_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint
        $this->addSql('ALTER TABLE event_persons DROP CONSTRAINT FK_5387574A71F7E88B');

        // Drop index
        $this->addSql('DROP INDEX IDX_5387574A71F7E88B');

        // Drop event_persons table
        $this->addSql('DROP TABLE event_persons');
    }
}
