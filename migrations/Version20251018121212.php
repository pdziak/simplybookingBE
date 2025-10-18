<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251018121212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create orders table with exact SQL specification';
    }

    public function up(Schema $schema): void
    {
        // Create orders table with exact structure as specified
        $this->addSql('CREATE TABLE public.orders (
            id integer NOT NULL,
            firstname character varying(255) NOT NULL,
            lastname character varying(255) NOT NULL,
            email character varying(255) NOT NULL,
            shipping_location character varying(50) NOT NULL,
            shipping_address text,
            created_at timestamp(0) without time zone NOT NULL,
            modified_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
            user_id integer NOT NULL,
            app_id integer NOT NULL
        )');
        
        // Set table owner
        $this->addSql('ALTER TABLE public.orders OWNER TO app');
        
        // Create sequence and set it as default for id column
        $this->addSql('CREATE SEQUENCE public.orders_id_seq
            AS integer
            START WITH 1
            INCREMENT BY 1
            NO MINVALUE
            NO MAXVALUE
            CACHE 1');
            
        $this->addSql('ALTER SEQUENCE public.orders_id_seq OWNER TO app');
        $this->addSql('ALTER TABLE public.orders ALTER COLUMN id SET DEFAULT nextval(\'public.orders_id_seq\'::regclass)');
        $this->addSql('ALTER TABLE public.orders ALTER COLUMN id SET NOT NULL');
        $this->addSql('ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id');
    }

    public function down(Schema $schema): void
    {
        // Drop the sequence and table
        $this->addSql('DROP SEQUENCE IF EXISTS public.orders_id_seq CASCADE');
        $this->addSql('DROP TABLE IF EXISTS public.orders');
    }
}
