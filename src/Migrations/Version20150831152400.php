<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20150831152400 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE items ADD draft TINYINT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE zzz_items ADD draft TINYINT NOT NULL DEFAULT 0');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE items DROP COLUMN draft');
        $this->addSql('ALTER TABLE zzz_items DROP COLUMN draft');
    }
}
