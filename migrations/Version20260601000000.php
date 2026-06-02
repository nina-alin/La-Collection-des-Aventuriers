<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace UserBook.status enum with is_owned, is_to_read, is_to_buy boolean columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_book ADD is_owned BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE user_book ADD is_to_read BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE user_book ADD is_to_buy BOOLEAN NOT NULL DEFAULT FALSE');

        $this->addSql("UPDATE user_book SET is_owned = TRUE WHERE status = 'dans-ma-collection'");
        $this->addSql("UPDATE user_book SET is_to_buy = TRUE WHERE status = 'a-acheter'");
        $this->addSql("UPDATE user_book SET is_to_read = TRUE WHERE status = 'a-lire'");

        $this->addSql("DELETE FROM user_book WHERE status IN ('lu', 'pas-dans-ma-collection')");

        $this->addSql('ALTER TABLE user_book DROP COLUMN status');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user_book ADD status VARCHAR(30) NOT NULL DEFAULT 'dans-ma-collection'");

        $this->addSql("UPDATE user_book SET status = 'dans-ma-collection' WHERE is_owned = TRUE");
        $this->addSql("UPDATE user_book SET status = 'a-acheter' WHERE is_to_buy = TRUE AND is_owned = FALSE");
        $this->addSql("UPDATE user_book SET status = 'a-lire' WHERE is_to_read = TRUE AND is_owned = FALSE AND is_to_buy = FALSE");

        $this->addSql('ALTER TABLE user_book DROP COLUMN is_owned');
        $this->addSql('ALTER TABLE user_book DROP COLUMN is_to_read');
        $this->addSql('ALTER TABLE user_book DROP COLUMN is_to_buy');
    }
}
