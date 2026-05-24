<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RBAC feature (004): add status/deletedAt to user, create work_entry, correction_proposal, moderation_log tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE \"user\" ADD status VARCHAR(10) NOT NULL DEFAULT 'active'");
        $this->addSql('ALTER TABLE "user" ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".deleted_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(<<<'SQL'
            CREATE TABLE work_entry (
                id UUID NOT NULL,
                author_id UUID DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                status VARCHAR(10) NOT NULL DEFAULT 'PENDING',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql("COMMENT ON COLUMN work_entry.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN work_entry.author_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN work_entry.created_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql(<<<'SQL'
            CREATE TABLE correction_proposal (
                id UUID NOT NULL,
                work_entry_id UUID NOT NULL,
                author_id UUID DEFAULT NULL,
                proposed_content JSON NOT NULL,
                status VARCHAR(10) NOT NULL DEFAULT 'PENDING',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql("COMMENT ON COLUMN correction_proposal.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN correction_proposal.work_entry_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN correction_proposal.author_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN correction_proposal.created_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql(<<<'SQL'
            CREATE TABLE moderation_log (
                id UUID NOT NULL,
                moderator_id VARCHAR(36) NOT NULL,
                action_type VARCHAR(10) NOT NULL,
                target_entity_type VARCHAR(100) NOT NULL,
                target_entity_id VARCHAR(36) NOT NULL,
                reason TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql("COMMENT ON COLUMN moderation_log.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN moderation_log.created_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql('ALTER TABLE work_entry ADD CONSTRAINT FK_WORK_ENTRY_AUTHOR FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE correction_proposal ADD CONSTRAINT FK_CORRECTION_PROPOSAL_WORK_ENTRY FOREIGN KEY (work_entry_id) REFERENCES work_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE correction_proposal ADD CONSTRAINT FK_CORRECTION_PROPOSAL_AUTHOR FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE correction_proposal DROP CONSTRAINT FK_CORRECTION_PROPOSAL_WORK_ENTRY');
        $this->addSql('ALTER TABLE correction_proposal DROP CONSTRAINT FK_CORRECTION_PROPOSAL_AUTHOR');
        $this->addSql('ALTER TABLE work_entry DROP CONSTRAINT FK_WORK_ENTRY_AUTHOR');
        $this->addSql('DROP TABLE moderation_log');
        $this->addSql('DROP TABLE correction_proposal');
        $this->addSql('DROP TABLE work_entry');
        $this->addSql('ALTER TABLE "user" DROP status');
        $this->addSql('ALTER TABLE "user" DROP deleted_at');
    }
}
