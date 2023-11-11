<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231111094120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update hash table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX caldav ON hash');
        $this->addSql('ALTER TABLE hash DROP caldav, CHANGE user_item_id user_item_id INT AUTO_INCREMENT NOT NULL, CHANGE rss rss VARCHAR(32) NOT NULL, CHANGE ical ical VARCHAR(32) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hash ADD caldav CHAR(32) DEFAULT NULL, CHANGE user_item_id user_item_id INT NOT NULL, CHANGE rss rss CHAR(32) DEFAULT NULL, CHANGE ical ical CHAR(32) DEFAULT NULL');
        $this->addSql('CREATE INDEX caldav ON hash (caldav)');
    }
}
