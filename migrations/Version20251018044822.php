<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251018044822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create budgets table for app budget management';
    }

    public function up(Schema $schema): void
    {
        // Create budgets table
        $this->addSql('CREATE SEQUENCE budgets_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE budgets (
            id INT NOT NULL DEFAULT nextval(\'budgets_id_seq\'), 
            user_id INT NOT NULL, 
            app_id INT NOT NULL, 
            budget NUMERIC(10, 2) NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_7F13F14AA76ED395 ON budgets (user_id)');
        $this->addSql('CREATE INDEX IDX_7F13F14A7987212D ON budgets (app_id)');
        $this->addSql('ALTER TABLE budgets ADD CONSTRAINT FK_7F13F14AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE budgets ADD CONSTRAINT FK_7F13F14A7987212D FOREIGN KEY (app_id) REFERENCES apps (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE budgets_id_seq CASCADE');
        $this->addSql('DROP TABLE budgets');
    }
}
