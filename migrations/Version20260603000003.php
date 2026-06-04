<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at to book table for nouveautes ordering (018)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("UPDATE book SET updated_at = NOW() WHERE updated_at IS NULL");
        $this->addSql("COMMENT ON COLUMN book.updated_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book DROP updated_at');
    }
}
