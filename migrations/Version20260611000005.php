<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'M5: Insert GhostUser row (requires M1 schema)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO \"user\" (
            id, email, pseudo, display_name, roles, status, is_email_verified,
            deleted_at, created_at, login_streak, password, google_id, avatar_url,
            timezone, last_login_at, previous_login_at
        ) VALUES (
            '00000000-0000-0000-0000-000000000000',
            'ghost@deleted.local',
            'ancien-aventurier',
            'un ancien aventurier',
            '[\"ROLE_USER\"]',
            'active',
            true,
            NULL,
            NOW(),
            0,
            NULL,
            NULL,
            NULL,
            NULL,
            NULL,
            NULL
        ) ON CONFLICT DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM \"user\" WHERE email = 'ghost@deleted.local'");
    }
}
