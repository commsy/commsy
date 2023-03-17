<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230315144421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Multiple slugs per room';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE room_slug (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, slug VARCHAR(255) NOT NULL, INDEX IDX_FB99710654177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE room_slug ADD CONSTRAINT FK_FB99710654177093 FOREIGN KEY (room_id) REFERENCES room (item_id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FB997106989D9B62 ON room_slug (slug)');

        $this->addSql('INSERT INTO room_slug (room_id, slug) SELECT room.item_id, room.slug FROM room WHERE room.slug IS NOT NULL');
        $this->addSql('ALTER TABLE room DROP slug');
        $this->addSql('ALTER TABLE room_privat DROP slug');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
