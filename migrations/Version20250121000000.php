<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Re-add email verification token fields to users table
 */
final class Version20250121000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Re-add email verification token fields to users table';
    }

    public function up(Schema $schema): void
    {
        // Add email verification token fields back to users table
        $this->addSql('ALTER TABLE users ADD email_verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD email_verification_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove email verification token fields
        $this->addSql('ALTER TABLE users DROP email_verification_token');
        $this->addSql('ALTER TABLE users DROP email_verification_token_expires_at');
    }
}
