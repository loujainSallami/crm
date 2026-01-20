<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116221951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adjust column lengths for crm_users table to VARCHAR(255)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crm_users ALTER "user" TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE crm_users ALTER pass TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE crm_users ALTER full_name TYPE VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crm_users ALTER "user" TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE crm_users ALTER pass TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE crm_users ALTER full_name TYPE VARCHAR(20)');
    }
}