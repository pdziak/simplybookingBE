<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add foreign key constraint to events table
 */
final class Version20250120000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign key constraint to events table linking to users table';
    }

    public function up(Schema $schema): void
    {
        // Add foreign key constraint to users table
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint
        $this->addSql('ALTER TABLE events DROP CONSTRAINT FK_5387574AA76ED395');
    }
}
