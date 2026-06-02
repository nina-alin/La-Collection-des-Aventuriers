<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260602000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification, notification_preference, user_collection_subscription tables and add user.timezone (017)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD timezone VARCHAR(50) DEFAULT NULL');

        $this->addSql('CREATE TABLE notification (id SERIAL NOT NULL, user_id UUID NOT NULL, type VARCHAR(50) NOT NULL, message TEXT NOT NULL, target_url VARCHAR(1024) DEFAULT NULL, is_read BOOLEAN NOT NULL DEFAULT false, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, source_id VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_notification_user_source ON notification (user_id, source_id)');
        $this->addSql('CREATE INDEX idx_notification_user_read_date ON notification (user_id, is_read, created_at)');
        $this->addSql('CREATE INDEX idx_notification_user_date ON notification (user_id, created_at)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_notification_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN notification.created_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE notification_preference (id SERIAL NOT NULL, user_id UUID NOT NULL, contribution_validated BOOLEAN NOT NULL DEFAULT true, book_activity BOOLEAN NOT NULL DEFAULT true, moderation_pending BOOLEAN NOT NULL DEFAULT true, rank_up BOOLEAN NOT NULL DEFAULT true, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_notification_preference_user ON notification_preference (user_id)');
        $this->addSql('ALTER TABLE notification_preference ADD CONSTRAINT FK_notification_preference_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE user_collection_subscription (id SERIAL NOT NULL, user_id UUID NOT NULL, collection_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_collection_sub ON user_collection_subscription (user_id, collection_id)');
        $this->addSql('CREATE INDEX idx_collection_sub_collection ON user_collection_subscription (collection_id)');
        $this->addSql('ALTER TABLE user_collection_subscription ADD CONSTRAINT FK_user_collection_sub_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_subscription ADD CONSTRAINT FK_user_collection_sub_collection FOREIGN KEY (collection_id) REFERENCES collection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN user_collection_subscription.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_notification_user');
        $this->addSql('DROP TABLE notification');

        $this->addSql('ALTER TABLE notification_preference DROP CONSTRAINT FK_notification_preference_user');
        $this->addSql('DROP TABLE notification_preference');

        $this->addSql('ALTER TABLE user_collection_subscription DROP CONSTRAINT FK_user_collection_sub_user');
        $this->addSql('ALTER TABLE user_collection_subscription DROP CONSTRAINT FK_user_collection_sub_collection');
        $this->addSql('DROP TABLE user_collection_subscription');

        $this->addSql('ALTER TABLE "user" DROP timezone');
    }
}
