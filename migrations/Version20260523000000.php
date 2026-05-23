<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user and cache_items tables for auth feature (002)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (
                id UUID NOT NULL,
                email VARCHAR(180) NOT NULL,
                pseudo VARCHAR(30) NOT NULL,
                password VARCHAR(255) DEFAULT NULL,
                roles JSON NOT NULL,
                google_id VARCHAR(255) DEFAULT NULL,
                display_name VARCHAR(255) DEFAULT NULL,
                avatar_url VARCHAR(2048) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F5D16E51 ON "user" (pseudo)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON "user" (google_id)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(<<<'SQL'
            CREATE TABLE cache_items (
                item_id VARCHAR(255) NOT NULL,
                item_data BYTEA NOT NULL,
                item_lifetime INT DEFAULT NULL,
                item_time INT NOT NULL,
                PRIMARY KEY(item_id)
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE cache_items');
    }
}
