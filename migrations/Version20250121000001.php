<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make password field nullable to support OAuth users
 */
final class Version20250121000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make password field nullable to support OAuth users without passwords';
    }

    public function up(Schema $schema): void
    {
        // Make password field nullable to support OAuth users
        $this->addSql('ALTER TABLE users ALTER COLUMN password DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert password field to NOT NULL (this will fail if there are OAuth users with null passwords)
        $this->addSql('ALTER TABLE users ALTER COLUMN password SET NOT NULL');
    }
}
