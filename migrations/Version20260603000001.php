<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity_event table with indexes (018)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE activity_event (id SERIAL NOT NULL, actor_user_id UUID NOT NULL, type VARCHAR(20) NOT NULL, actor_initials VARCHAR(4) DEFAULT NULL, actor_pseudo VARCHAR(30) NOT NULL, book_title VARCHAR(255) DEFAULT NULL, book_slug VARCHAR(255) DEFAULT NULL, status_badge VARCHAR(20) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE INDEX idx_activity_event_created_at ON activity_event (created_at)");
        $this->addSql("CREATE INDEX idx_activity_event_type_created_at ON activity_event (type, created_at)");
        $this->addSql('ALTER TABLE activity_event ADD CONSTRAINT FK_activity_event_actor_user FOREIGN KEY (actor_user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql("COMMENT ON COLUMN activity_event.created_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_event DROP CONSTRAINT FK_activity_event_actor_user');
        $this->addSql('DROP TABLE activity_event');
    }
}
