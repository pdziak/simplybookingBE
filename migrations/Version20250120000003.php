<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create events table
 */
final class Version20250120000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create events table with user relationship';
    }

    public function up(Schema $schema): void
    {
        // Create events table without foreign key constraint first
        $this->addSql('CREATE TABLE events (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            location VARCHAR(500) DEFAULT NULL,
            user_id INT NOT NULL,
            datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        )');

        // Create index on user_id for better query performance
        $this->addSql('CREATE INDEX IDX_5387574AA76ED395 ON events (user_id)');

        // Create index on datetime for better query performance
        $this->addSql('CREATE INDEX IDX_5387574A9F94988C ON events (datetime)');

        // Note: Foreign key constraint will be added in a separate migration
        // once we ensure the users table has proper primary key structure
    }

    public function down(Schema $schema): void
    {
        // Drop indexes
        $this->addSql('DROP INDEX IDX_5387574AA76ED395');
        $this->addSql('DROP INDEX IDX_5387574A9F94988C');

        // Drop events table
        $this->addSql('DROP TABLE events');
    }
}
