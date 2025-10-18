<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250120000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make cart_id nullable in cart_items table to allow proper cart item removal';
    }

    public function up(Schema $schema): void
    {
        // Make cart_id nullable in cart_items table
        $this->addSql('ALTER TABLE cart_items ALTER COLUMN cart_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert cart_id to NOT NULL
        $this->addSql('ALTER TABLE cart_items ALTER COLUMN cart_id SET NOT NULL');
    }
}
