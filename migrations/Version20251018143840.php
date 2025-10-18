<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251018143840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create content table for storing simple content with id, title, slug, description, is_active fields';
    }

    public function up(Schema $schema): void
    {
        // Create content table
        $this->addSql('CREATE TABLE public.content (
            id integer NOT NULL,
            title character varying(255) NOT NULL,
            slug character varying(255) NOT NULL,
            description text,
            is_active boolean NOT NULL DEFAULT true,
            created_at timestamp(0) without time zone NOT NULL,
            updated_at timestamp(0) without time zone
        )');
        
        // Set table owner
        $this->addSql('ALTER TABLE public.content OWNER TO app');
        
        // Create sequence and set it as default for id column
        $this->addSql('CREATE SEQUENCE public.content_id_seq
            AS integer
            START WITH 1
            INCREMENT BY 1
            NO MINVALUE
            NO MAXVALUE
            CACHE 1');
            
        $this->addSql('ALTER SEQUENCE public.content_id_seq OWNER TO app');
        $this->addSql('ALTER TABLE public.content ALTER COLUMN id SET DEFAULT nextval(\'public.content_id_seq\'::regclass)');
        $this->addSql('ALTER TABLE public.content ALTER COLUMN id SET NOT NULL');
        $this->addSql('ALTER SEQUENCE public.content_id_seq OWNED BY public.content.id');
        
        // Add unique constraint for slug
        $this->addSql('ALTER TABLE public.content ADD CONSTRAINT content_slug_unique UNIQUE (slug)');
    }

    public function down(Schema $schema): void
    {
        // Drop the sequence and table
        $this->addSql('DROP SEQUENCE IF EXISTS public.content_id_seq CASCADE');
        $this->addSql('DROP TABLE IF EXISTS public.content');
    }
}
