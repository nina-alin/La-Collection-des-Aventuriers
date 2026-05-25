<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525121111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add collection table and collection_id FK on book';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE collection (id UUID NOT NULL, nom VARCHAR(255) NOT NULL, nom_original VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, description TEXT NOT NULL, genre VARCHAR(50) NOT NULL, createurs JSON NOT NULL, annee_creation SMALLINT DEFAULT NULL, editeur_historique VARCHAR(255) DEFAULT NULL, statut VARCHAR(20) NOT NULL, image_logo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_collection_slug ON collection (slug)');
        $this->addSql('CREATE UNIQUE INDEX uniq_collection_nom ON collection (nom)');
        $this->addSql('ALTER TABLE book ADD collection_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331514956FD FOREIGN KEY (collection_id) REFERENCES collection (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_book_collection_id ON book (collection_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book DROP CONSTRAINT FK_CBE5A331514956FD');
        $this->addSql('DROP INDEX idx_book_collection_id');
        $this->addSql('ALTER TABLE book DROP COLUMN collection_id');
        $this->addSql('DROP TABLE collection');
    }
}
