<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_login_at and previous_login_at to user table (018)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD previous_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN \"user\".last_login_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN \"user\".previous_login_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP last_login_at');
        $this->addSql('ALTER TABLE "user" DROP previous_login_at');
    }
}
