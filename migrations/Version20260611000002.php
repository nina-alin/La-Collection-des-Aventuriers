<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'M2: Make moderation_log.moderator_id nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE moderation_log ALTER COLUMN moderator_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE moderation_log SET moderator_id = \'unknown\' WHERE moderator_id IS NULL');
        $this->addSql('ALTER TABLE moderation_log ALTER COLUMN moderator_id SET NOT NULL');
    }
}
