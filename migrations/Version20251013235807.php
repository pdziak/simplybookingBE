<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013235807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix users table id column to use sequence as default value';
    }

    public function up(Schema $schema): void
    {
        // Fix the id column to use the sequence as default value
        $this->addSql('ALTER TABLE users ALTER COLUMN id SET DEFAULT nextval(\'users_id_seq\')');
    }

    public function down(Schema $schema): void
    {
        // Remove the default value from id column
        $this->addSql('ALTER TABLE users ALTER COLUMN id DROP DEFAULT');
    }
}
