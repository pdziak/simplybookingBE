<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add product_stock and product_sku fields to products table
 */
final class Version20250116000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product_stock and product_sku fields to products table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products ADD product_stock INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE products ADD product_sku VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products DROP product_stock');
        $this->addSql('ALTER TABLE products DROP product_sku');
    }
}
