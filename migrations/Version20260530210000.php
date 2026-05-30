<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260530210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '011-contributor-page: add suggestion, suggestion_refusal, contributor_level tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE contributor_level (
                id SERIAL NOT NULL,
                name VARCHAR(100) NOT NULL,
                rank_number SMALLINT NOT NULL,
                threshold INT NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CONTRIBUTOR_LEVEL_RANK_NUMBER ON contributor_level (rank_number)');

        $this->addSql('
            CREATE TABLE suggestion (
                id UUID NOT NULL,
                user_id UUID NOT NULL,
                entity_type VARCHAR(255) NOT NULL,
                mode VARCHAR(255) NOT NULL,
                source_entity_id UUID DEFAULT NULL,
                source_entity_type VARCHAR(255) DEFAULT NULL,
                form_data JSON NOT NULL,
                status VARCHAR(255) NOT NULL DEFAULT \'PENDING\',
                cover_image_path VARCHAR(255) DEFAULT NULL,
                submitted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX idx_suggestion_user_status ON suggestion (user_id, status)');
        $this->addSql('COMMENT ON COLUMN suggestion.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN suggestion.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN suggestion.source_entity_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN suggestion.submitted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE suggestion ADD CONSTRAINT FK_SUGGESTION_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('
            CREATE TABLE suggestion_refusal (
                id UUID NOT NULL,
                suggestion_id UUID NOT NULL,
                moderator_id UUID DEFAULT NULL,
                reason TEXT NOT NULL,
                actions JSON NOT NULL,
                refused_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SUGGESTION_REFUSAL_SUGGESTION ON suggestion_refusal (suggestion_id)');
        $this->addSql('COMMENT ON COLUMN suggestion_refusal.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN suggestion_refusal.suggestion_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN suggestion_refusal.moderator_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN suggestion_refusal.refused_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE suggestion_refusal ADD CONSTRAINT FK_SUGGESTION_REFUSAL_SUGGESTION FOREIGN KEY (suggestion_id) REFERENCES suggestion (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE suggestion_refusal ADD CONSTRAINT FK_SUGGESTION_REFUSAL_MODERATOR FOREIGN KEY (moderator_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suggestion_refusal DROP CONSTRAINT FK_SUGGESTION_REFUSAL_SUGGESTION');
        $this->addSql('ALTER TABLE suggestion_refusal DROP CONSTRAINT FK_SUGGESTION_REFUSAL_MODERATOR');
        $this->addSql('ALTER TABLE suggestion DROP CONSTRAINT FK_SUGGESTION_USER');
        $this->addSql('DROP TABLE suggestion_refusal');
        $this->addSql('DROP TABLE suggestion');
        $this->addSql('DROP TABLE contributor_level');
    }
}
