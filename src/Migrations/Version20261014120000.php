<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Moves the migrations' own bookkeeping table to utf8mb4.
 *
 * Doctrine Migrations creates this table itself and never alters its charset
 * afterwards. Under DBAL 3 it was created as utf8mb3; under DBAL 4 it takes
 * whatever the database defaults to. Converting it here gives every
 * installation the same table, old and new alike.
 */
final class Version20261014120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert migration_versions to utf8mb4';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE migration_versions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE migration_versions CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
    }
}
