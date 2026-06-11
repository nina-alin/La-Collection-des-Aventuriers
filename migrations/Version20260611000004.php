<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'M4: Create user_contributor_subscription table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_contributor_subscription (
            id SERIAL NOT NULL,
            user_id UUID NOT NULL,
            contributor_id UUID NOT NULL,
            subscribed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_contrib_sub ON user_contributor_subscription (user_id, contributor_id)');
        $this->addSql('ALTER TABLE user_contributor_subscription ADD CONSTRAINT fk_user_contrib_sub_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_contributor_subscription ADD CONSTRAINT fk_user_contrib_sub_contrib FOREIGN KEY (contributor_id) REFERENCES contributor (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_contributor_subscription DROP CONSTRAINT fk_user_contrib_sub_user');
        $this->addSql('ALTER TABLE user_contributor_subscription DROP CONSTRAINT fk_user_contrib_sub_contrib');
        $this->addSql('DROP TABLE user_contributor_subscription');
    }
}
