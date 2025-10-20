<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add login column to users table
 */
final class Version20250120000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add login column to users table';
    }

    public function up(Schema $schema): void
    {
        // Add login column to users table
        $this->addSql('ALTER TABLE users ADD login VARCHAR(50) DEFAULT NULL');
        
        // Create unique index on login column
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9AA08CB10 ON users (login)');
    }

    public function down(Schema $schema): void
    {
        // Drop unique index
        $this->addSql('DROP INDEX UNIQ_1483A5E9AA08CB10');
        
        // Drop login column
        $this->addSql('ALTER TABLE users DROP login');
    }
}
