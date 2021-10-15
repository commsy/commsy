<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211015075059 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remove matrix data';
    }

    public function up(Schema $schema) : void
    {
        // Remove from items / labels table
        $this->addSql('DELETE labels, items FROM labels LEFT JOIN items ON labels.item_id = items.item_id WHERE labels.type = "matrix"');
        $this->addSql('DELETE zzz_labels, zzz_items FROM zzz_labels LEFT JOIN zzz_items ON zzz_labels.item_id = zzz_items.item_id WHERE zzz_labels.type = "matrix"');

        // Remove links of type "in_mylist"
        $this->addSql('DELETE FROM links WHERE links.link_type = "in_matrix"');
        $this->addSql('DELETE FROM zzz_links WHERE zzz_links.link_type = "in_matrix"');
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
