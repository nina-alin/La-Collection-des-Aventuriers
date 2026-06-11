<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'M3: Create user_list_visibility table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_list_visibility (
            id SERIAL NOT NULL,
            user_id UUID NOT NULL,
            list_type VARCHAR(20) NOT NULL,
            is_public BOOLEAN NOT NULL DEFAULT FALSE,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_list_visibility ON user_list_visibility (user_id, list_type)');
        $this->addSql('ALTER TABLE user_list_visibility ADD CONSTRAINT fk_user_list_vis_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_list_visibility DROP CONSTRAINT fk_user_list_vis_user');
        $this->addSql('DROP TABLE user_list_visibility');
    }
}
