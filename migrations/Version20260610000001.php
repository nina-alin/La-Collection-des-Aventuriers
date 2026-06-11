<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_followed_contributor table and follow_notification_sent_at column on book';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_followed_contributor (
            id INT GENERATED ALWAYS AS IDENTITY NOT NULL,
            user_id UUID NOT NULL,
            contributor_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_followed_contributor ON user_followed_contributor (user_id, contributor_id)');
        $this->addSql('CREATE INDEX idx_user_followed_contributor_contrib ON user_followed_contributor (contributor_id)');
        $this->addSql('ALTER TABLE user_followed_contributor ADD CONSTRAINT fk_ufc_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_followed_contributor ADD CONSTRAINT fk_ufc_contributor FOREIGN KEY (contributor_id) REFERENCES contributor (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE book ADD follow_notification_sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_followed_contributor DROP CONSTRAINT fk_ufc_user');
        $this->addSql('ALTER TABLE user_followed_contributor DROP CONSTRAINT fk_ufc_contributor');
        $this->addSql('DROP TABLE user_followed_contributor');
        $this->addSql('ALTER TABLE book DROP follow_notification_sent_at');
    }
}
