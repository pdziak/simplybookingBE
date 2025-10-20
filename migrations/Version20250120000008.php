<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add email_verification_token column to users table
 */
final class Version20250120000008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email_verification_token column to users table';
    }

    public function up(Schema $schema): void
    {
        // Add email_verification_token column to users table
        $this->addSql('ALTER TABLE users ADD email_verification_token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Drop email_verification_token column
        $this->addSql('ALTER TABLE users DROP email_verification_token');
    }
}
