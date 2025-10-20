<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add google_id column to users table
 */
final class Version20250120000006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add google_id column to users table';
    }

    public function up(Schema $schema): void
    {
        // Add google_id column to users table
        $this->addSql('ALTER TABLE users ADD google_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Drop google_id column
        $this->addSql('ALTER TABLE users DROP google_id');
    }
}
