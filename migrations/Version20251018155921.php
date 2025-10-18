<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251018155921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create contact_submissions table for storing contact form submissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE public.contact_submissions (
            id integer NOT NULL,
            company character varying(255) NOT NULL,
            email character varying(255) NOT NULL,
            content text NOT NULL,
            is_processed boolean NOT NULL DEFAULT false,
            created_at timestamp(0) without time zone NOT NULL,
            processed_at timestamp(0) without time zone
        )');
        $this->addSql('ALTER TABLE public.contact_submissions OWNER TO app');
        $this->addSql('CREATE SEQUENCE public.contact_submissions_id_seq
            AS integer
            START WITH 1
            INCREMENT BY 1
            NO MINVALUE
            NO MAXVALUE
            CACHE 1');
        $this->addSql('ALTER SEQUENCE public.contact_submissions_id_seq OWNER TO app');
        $this->addSql('ALTER TABLE public.contact_submissions ALTER COLUMN id SET DEFAULT nextval(\'public.contact_submissions_id_seq\'::regclass)');
        $this->addSql('ALTER TABLE public.contact_submissions ALTER COLUMN id SET NOT NULL');
        $this->addSql('ALTER SEQUENCE public.contact_submissions_id_seq OWNED BY public.contact_submissions.id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE IF EXISTS public.contact_submissions_id_seq CASCADE');
        $this->addSql('DROP TABLE IF EXISTS public.contact_submissions');
    }
}
