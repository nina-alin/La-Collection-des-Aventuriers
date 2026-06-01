<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_book table for catalogue feature (015)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_book (id SERIAL NOT NULL, user_id UUID NOT NULL, book_id INT NOT NULL, status VARCHAR(30) NOT NULL DEFAULT \'dans-ma-collection\', is_favorite BOOLEAN NOT NULL DEFAULT false, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_book ON user_book (user_id, book_id)');
        $this->addSql('CREATE INDEX idx_user_book_user_id ON user_book (user_id)');
        $this->addSql('CREATE INDEX idx_user_book_book_id ON user_book (book_id)');
        $this->addSql('ALTER TABLE user_book ADD CONSTRAINT FK_user_book_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_book ADD CONSTRAINT FK_user_book_book FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN user_book.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_book.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_book DROP CONSTRAINT FK_user_book_user');
        $this->addSql('ALTER TABLE user_book DROP CONSTRAINT FK_user_book_book');
        $this->addSql('DROP TABLE user_book');
    }
}
