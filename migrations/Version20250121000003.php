<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250121000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modify datetime column to timestamp with time zone and remove separate timezone column';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events DROP COLUMN timezone');
        $this->addSql('ALTER TABLE events ALTER COLUMN datetime TYPE timestamp with time zone');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ALTER COLUMN datetime TYPE timestamp without time zone');
        $this->addSql('ALTER TABLE events ADD COLUMN timezone VARCHAR(50) NOT NULL');
    }
}
