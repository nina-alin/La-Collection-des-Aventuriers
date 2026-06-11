<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'M1: Add login_streak, last_login_date, pending_email, email_change_token, email_token_expires_at to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD login_streak INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE "user" ADD last_login_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD pending_email VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD email_change_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD email_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD region VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP login_streak');
        $this->addSql('ALTER TABLE "user" DROP last_login_date');
        $this->addSql('ALTER TABLE "user" DROP pending_email');
        $this->addSql('ALTER TABLE "user" DROP email_change_token');
        $this->addSql('ALTER TABLE "user" DROP email_token_expires_at');
        $this->addSql('ALTER TABLE "user" DROP region');
    }
}
