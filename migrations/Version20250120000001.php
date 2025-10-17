<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250120000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cart and cart_items tables for cart persistence';
    }

    public function up(Schema $schema): void
    {
        // Create carts table
        $this->addSql('CREATE SEQUENCE carts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE carts (
            id INT NOT NULL, 
            user_id INT NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_4E004AAC_A76ED395 ON carts (user_id)');
        $this->addSql('ALTER TABLE carts ADD CONSTRAINT FK_4E004AAC_A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Create cart_items table
        $this->addSql('CREATE SEQUENCE cart_items_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE cart_items (
            id INT NOT NULL, 
            cart_id INT NOT NULL, 
            product_id INT NOT NULL, 
            quantity INT NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_BEF484451AD5CDBF ON cart_items (cart_id)');
        $this->addSql('CREATE INDEX IDX_BEF484454584665A ON cart_items (product_id)');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF484451AD5CDBF FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF484454584665A FOREIGN KEY (product_id) REFERENCES products (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE carts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE cart_items_id_seq CASCADE');
        $this->addSql('DROP TABLE carts');
        $this->addSql('DROP TABLE cart_items');
    }
}
