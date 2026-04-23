<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328233730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix notification.createdAt type to timestamp';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE notification
            ALTER COLUMN createdat TYPE TIMESTAMP(0) WITHOUT TIME ZONE
            USING createdat::timestamp(0) without time zone
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE notification
            ALTER COLUMN createdat TYPE VARCHAR(255)
            USING createdat::varchar
        SQL);
    }
}