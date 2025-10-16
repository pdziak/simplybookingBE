<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id foreign key to apps table';
    }

    public function up(Schema $schema): void
    {
        // First add the column as nullable
        $this->addSql('ALTER TABLE apps ADD user_id INT');
        
        // Get the first user ID or create a default user
        $this->addSql('UPDATE apps SET user_id = (SELECT id FROM users LIMIT 1) WHERE user_id IS NULL');
        
        // Now make it NOT NULL
        $this->addSql('ALTER TABLE apps ALTER COLUMN user_id SET NOT NULL');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE apps ADD CONSTRAINT FK_F5037C2BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_F5037C2BA76ED395 ON apps (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE apps DROP CONSTRAINT FK_F5037C2BA76ED395');
        $this->addSql('DROP INDEX IDX_F5037C2BA76ED395');
        $this->addSql('ALTER TABLE apps DROP user_id');
    }
}
