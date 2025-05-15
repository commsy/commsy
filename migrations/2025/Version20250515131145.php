<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250515131145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop portfolio';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE annotations, items FROM annotations INNER JOIN items ON annotations.item_id = items.item_id WHERE annotations.item_id IN (SELECT GROUP_CONCAT(annotation_portfolio.a_id) FROM annotation_portfolio)');
        $this->addSql('DROP TABLE annotation_portfolio');

        $this->addSql('DROP TABLE template_portfolio');
        $this->addSql('DROP TABLE user_portfolio');
        $this->addSql('DROP TABLE tag_portfolio');

        $this->addSql('DROP TABLE portfolio');
        $this->addSql('DELETE FROM items WHERE type = "portfolio"');

        DbConverter::removeExtra($this->connection, 'room_privat', 'item_id', ['CS_BAR_SHOW_PORTFOLIO']);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
