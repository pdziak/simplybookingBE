<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250118000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order_products table to store order product details';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_products (id SERIAL NOT NULL, order_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_price NUMERIC(10, 2) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_ORDER_PRODUCTS_ORDER_ID ON order_products (order_id)');
        $this->addSql('CREATE INDEX IDX_ORDER_PRODUCTS_PRODUCT_ID ON order_products (product_id)');
        $this->addSql('ALTER TABLE order_products ADD CONSTRAINT FK_ORDER_PRODUCTS_ORDER_ID FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_products ADD CONSTRAINT FK_ORDER_PRODUCTS_PRODUCT_ID FOREIGN KEY (product_id) REFERENCES products (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_products DROP CONSTRAINT FK_ORDER_PRODUCTS_ORDER_ID');
        $this->addSql('ALTER TABLE order_products DROP CONSTRAINT FK_ORDER_PRODUCTS_PRODUCT_ID');
        $this->addSql('DROP TABLE order_products');
    }
}
