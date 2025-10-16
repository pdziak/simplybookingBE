<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_apps many-to-many relationship table';
    }

    public function up(Schema $schema): void
    {
        // Create user_apps table
        $this->addSql('CREATE TABLE user_apps (user_id INT NOT NULL, app_id INT NOT NULL, PRIMARY KEY(user_id, app_id))');
        $this->addSql('CREATE INDEX IDX_8F7C2C7CA76ED395 ON user_apps (user_id)');
        $this->addSql('CREATE INDEX IDX_8F7C2C7C7987212C ON user_apps (app_id)');
        $this->addSql('ALTER TABLE user_apps ADD CONSTRAINT FK_8F7C2C7CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_apps ADD CONSTRAINT FK_8F7C2C7C7987212C FOREIGN KEY (app_id) REFERENCES apps (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop user_apps table
        $this->addSql('DROP TABLE user_apps');
    }
}
