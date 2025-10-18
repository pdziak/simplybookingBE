<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251018121350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order_products table for storing order line items';
    }

    public function up(Schema $schema): void
    {
        // Create order_products table with exact structure as specified
        $this->addSql('CREATE TABLE public.order_products (
            id integer NOT NULL,
            order_id integer NOT NULL,
            product_id integer NOT NULL,
            quantity integer NOT NULL,
            unit_price numeric(10,2) NOT NULL,
            total_price numeric(10,2) NOT NULL,
            created_at timestamp(0) without time zone NOT NULL
        )');
        
        // Set table owner
        $this->addSql('ALTER TABLE public.order_products OWNER TO app');
        
        // Create sequence and set it as default for id column
        $this->addSql('CREATE SEQUENCE public.order_products_id_seq
            AS integer
            START WITH 1
            INCREMENT BY 1
            NO MINVALUE
            NO MAXVALUE
            CACHE 1');
            
        $this->addSql('ALTER SEQUENCE public.order_products_id_seq OWNER TO app');
        $this->addSql('ALTER TABLE public.order_products ALTER COLUMN id SET DEFAULT nextval(\'public.order_products_id_seq\'::regclass)');
        $this->addSql('ALTER TABLE public.order_products ALTER COLUMN id SET NOT NULL');
        $this->addSql('ALTER SEQUENCE public.order_products_id_seq OWNED BY public.order_products.id');
    }

    public function down(Schema $schema): void
    {
        // Drop the sequence and table
        $this->addSql('DROP SEQUENCE IF EXISTS public.order_products_id_seq CASCADE');
        $this->addSql('DROP TABLE IF EXISTS public.order_products');
    }
}
