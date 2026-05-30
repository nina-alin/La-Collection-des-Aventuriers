<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260530200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '010-auth-pages: add isEmailVerified to user, create reset_password_token, email_verification_token, rememberme_token tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD is_email_verified BOOLEAN NOT NULL DEFAULT false');
        // Grandfather existing users — they registered before email verification was introduced
        $this->addSql('UPDATE "user" SET is_email_verified = true');

        $this->addSql('
            CREATE TABLE reset_password_token (
                id SERIAL NOT NULL,
                user_id UUID NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                used BOOLEAN NOT NULL DEFAULT false,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_RESET_PASSWORD_TOKEN_TOKEN ON reset_password_token (token)');
        $this->addSql('CREATE INDEX idx_reset_token_user_used_expires ON reset_password_token (user_id, used, expires_at)');
        $this->addSql('COMMENT ON COLUMN reset_password_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reset_password_token ADD CONSTRAINT FK_RESET_PASSWORD_TOKEN_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('
            CREATE TABLE email_verification_token (
                id SERIAL NOT NULL,
                user_id UUID NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EMAIL_VERIFICATION_TOKEN_TOKEN ON email_verification_token (token)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EMAIL_VERIFICATION_TOKEN_USER ON email_verification_token (user_id)');
        $this->addSql('COMMENT ON COLUMN email_verification_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN email_verification_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE email_verification_token ADD CONSTRAINT FK_EMAIL_VERIFICATION_TOKEN_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('
            CREATE TABLE rememberme_token (
                series VARCHAR(88) NOT NULL,
                value VARCHAR(88) NOT NULL,
                lastUsed TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                class VARCHAR(100) NOT NULL,
                username VARCHAR(200) NOT NULL,
                PRIMARY KEY(series)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reset_password_token DROP CONSTRAINT FK_RESET_PASSWORD_TOKEN_USER');
        $this->addSql('ALTER TABLE email_verification_token DROP CONSTRAINT FK_EMAIL_VERIFICATION_TOKEN_USER');
        $this->addSql('DROP TABLE reset_password_token');
        $this->addSql('DROP TABLE email_verification_token');
        $this->addSql('DROP TABLE rememberme_token');
        $this->addSql('ALTER TABLE "user" DROP is_email_verified');
    }
}
