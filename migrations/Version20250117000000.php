<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to drop unique index uniq_5c4695f5e7927c74
 */
final class Version20250117000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unique index uniq_5c4695f5e7927c74';
    }

    public function up(Schema $schema): void
    {
        // Drop the unique index
        $this->addSql('DROP INDEX IF EXISTS public.uniq_5c4695f5e7927c74');
    }

    public function down(Schema $schema): void
    {
        // This migration only drops an index, so we can't easily reverse it
        // without knowing the original table and column structure
        // If you need to recreate this index, you'll need to provide the original CREATE INDEX statement
        $this->addSql('-- Cannot automatically recreate dropped index uniq_5c4695f5e7927c74');
        $this->addSql('-- Please provide the original CREATE INDEX statement if rollback is needed');
    }
}
