<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225003454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment DROP CONSTRAINT fk_fe38f844a76ed395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_78a47793a76ed395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment RENAME COLUMN user_id TO crm_user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment ADD CONSTRAINT FK_78A477934B259F02 FOREIGN KEY (crm_user_id) REFERENCES crm_users (user_id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_78A477934B259F02 ON appointment (crm_user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP CONSTRAINT fk_cfbdfa14a76ed395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_6f8f552aa76ed395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note RENAME COLUMN user_id TO crm_user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD CONSTRAINT FK_6F8F552A4B259F02 FOREIGN KEY (crm_user_id) REFERENCES crm_users (user_id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6F8F552A4B259F02 ON note (crm_user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP CONSTRAINT fk_bf5476caa76ed395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_a765ad32a76ed395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification RENAME COLUMN user_id TO crm_user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_A765AD324B259F02 FOREIGN KEY (crm_user_id) REFERENCES crm_users (user_id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A765AD324B259F02 ON notification (crm_user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task DROP CONSTRAINT fk_527edb251f61b6d5
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_f24c741b1f61b6d5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task RENAME COLUMN vicidial_user_id TO crm_user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task ADD CONSTRAINT FK_F24C741B4B259F02 FOREIGN KEY (crm_user_id) REFERENCES crm_users (user_id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F24C741B4B259F02 ON task (crm_user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE Appointment DROP CONSTRAINT FK_78A477934B259F02
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_78A477934B259F02
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Appointment RENAME COLUMN crm_user_id TO user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Appointment ADD CONSTRAINT fk_fe38f844a76ed395 FOREIGN KEY (user_id) REFERENCES crm_users (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_78a47793a76ed395 ON Appointment (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Note DROP CONSTRAINT FK_6F8F552A4B259F02
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_6F8F552A4B259F02
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Note RENAME COLUMN crm_user_id TO user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Note ADD CONSTRAINT fk_cfbdfa14a76ed395 FOREIGN KEY (user_id) REFERENCES crm_users (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_6f8f552aa76ed395 ON Note (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Notification DROP CONSTRAINT FK_A765AD324B259F02
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_A765AD324B259F02
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Notification RENAME COLUMN crm_user_id TO user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Notification ADD CONSTRAINT fk_bf5476caa76ed395 FOREIGN KEY (user_id) REFERENCES crm_users (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_a765ad32a76ed395 ON Notification (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Task DROP CONSTRAINT FK_F24C741B4B259F02
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F24C741B4B259F02
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Task RENAME COLUMN crm_user_id TO vicidial_user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Task ADD CONSTRAINT fk_527edb251f61b6d5 FOREIGN KEY (vicidial_user_id) REFERENCES crm_users (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_f24c741b1f61b6d5 ON Task (vicidial_user_id)
        SQL);
    }
}
