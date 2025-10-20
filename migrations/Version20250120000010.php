<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix users.id column to be auto-incrementing
 */
final class Version20250120000010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix users.id column to be auto-incrementing primary key';
    }

    public function up(Schema $schema): void
    {
        // First, drop the foreign key constraint from events table
        $this->addSql('ALTER TABLE events DROP CONSTRAINT IF EXISTS FK_5387574AA76ED395');
        
        // Drop the existing primary key constraint
        $this->addSql('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_pkey');
        
        // Alter the id column to be SERIAL (auto-incrementing)
        $this->addSql('ALTER TABLE users ALTER COLUMN id SET DEFAULT nextval(\'users_id_seq\'::regclass)');
        
        // Create the sequence if it doesn't exist
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS users_id_seq OWNED BY users.id');
        
        // Set the sequence to start from the current max id + 1
        $this->addSql('SELECT setval(\'users_id_seq\', COALESCE((SELECT MAX(id) FROM users), 0) + 1, false)');
        
        // Add the primary key constraint back
        $this->addSql('ALTER TABLE users ADD CONSTRAINT users_pkey PRIMARY KEY (id)');
        
        // Recreate the foreign key constraint
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop the foreign key constraint from events table
        $this->addSql('ALTER TABLE events DROP CONSTRAINT IF EXISTS FK_5387574AA76ED395');
        
        // Drop the primary key constraint
        $this->addSql('ALTER TABLE users DROP CONSTRAINT users_pkey');
        
        // Remove the default value from id column
        $this->addSql('ALTER TABLE users ALTER COLUMN id DROP DEFAULT');
        
        // Drop the sequence
        $this->addSql('DROP SEQUENCE IF EXISTS users_id_seq');
        
        // Add back the original primary key constraint
        $this->addSql('ALTER TABLE users ADD CONSTRAINT users_pkey PRIMARY KEY (id)');
        
        // Recreate the foreign key constraint
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }
}
