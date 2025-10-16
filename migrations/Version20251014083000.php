<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014083000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create apps table for App entity';
    }

    public function up(Schema $schema): void
    {
        // Create apps table
        $this->addSql('CREATE SEQUENCE apps_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE apps (
            id INT NOT NULL DEFAULT nextval(\'apps_id_seq\'),
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            company_name VARCHAR(255) NOT NULL,
            email VARCHAR(180) NOT NULL,
            description TEXT DEFAULT NULL,
            logo VARCHAR(500) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5C4695F5989D9B62 ON apps (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5C4695F5E7927C74 ON apps (email)');
        $this->addSql('COMMENT ON COLUMN apps.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN apps.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // Drop apps table
        $this->addSql('DROP SEQUENCE apps_id_seq CASCADE');
        $this->addSql('DROP TABLE apps');
    }
}
