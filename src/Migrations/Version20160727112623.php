<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20160727112623 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS homepage_page');
        $this->addSql('DROP TABLE IF EXISTS homepage_link_page_page');
        $this->addSql('DROP TABLE IF EXISTS zzz_homepage_page');
        $this->addSql('DROP TABLE IF EXISTS zzz_homepage_link_page_page');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE homepage_page (
                item_id int(11) NOT NULL DEFAULT \'0\',
                context_id int(11) DEFAULT NULL,
                creator_id int(11) NOT NULL DEFAULT \'0\',
                modifier_id int(11) DEFAULT NULL,
                deleter_id int(11) DEFAULT NULL,
                creation_date datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                modification_date datetime DEFAULT NULL,
                deletion_date datetime DEFAULT NULL,
                title varchar(255) NOT NULL,
                description mediumtext,
                public tinyint(11) NOT NULL DEFAULT \'0\',
                page_type varchar(10) NOT NULL,
                PRIMARY KEY (item_id),
                KEY context_id (context_id),
                KEY creator_id (creator_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');

        $this->addSql('
            CREATE TABLE homepage_link_page_page (
                link_id int(11) NOT NULL AUTO_INCREMENT,
                from_item_id int(11) NOT NULL DEFAULT \'0\',
                to_item_id int(11) NOT NULL DEFAULT \'0\',
                context_id int(11) NOT NULL DEFAULT \'0\',
                creator_id int(11) NOT NULL DEFAULT \'0\',
                creation_date datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                modifier_id int(11) NOT NULL DEFAULT \'0\',
                modification_date datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                deleter_id int(11) DEFAULT NULL,
                deletion_date datetime DEFAULT NULL,
                sorting_place tinyint(4) DEFAULT NULL,
                PRIMARY KEY (link_id),
                KEY from_item_id (from_item_id),
                KEY context_id (context_id),
                KEY to_item_id (to_item_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');

        $this->addSql('
            CREATE TABLE zzz_homepage_page (
                item_id int(11) NOT NULL DEFAULT \'0\',
                context_id int(11) DEFAULT NULL,
                creator_id int(11) NOT NULL DEFAULT \'0\',
                modifier_id int(11) DEFAULT NULL,
                deleter_id int(11) DEFAULT NULL,
                creation_date datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                modification_date datetime DEFAULT NULL,
                deletion_date datetime DEFAULT NULL,
                title varchar(255) NOT NULL,
                description mediumtext,
                public tinyint(11) NOT NULL DEFAULT \'0\',
                page_type varchar(10) NOT NULL,
                PRIMARY KEY (item_id),
                KEY context_id (context_id),
                KEY creator_id (creator_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');

        $this->addSql('
            CREATE TABLE zzz_homepage_link_page_page (
                link_id int(11) NOT NULL AUTO_INCREMENT,
                from_item_id int(11) NOT NULL DEFAULT \'0\',
                to_item_id int(11) NOT NULL DEFAULT \'0\',
                context_id int(11) NOT NULL DEFAULT \'0\',
                creator_id int(11) NOT NULL DEFAULT \'0\',
                creation_date datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                modifier_id int(11) NOT NULL DEFAULT \'0\',
                modification_date datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                deleter_id int(11) DEFAULT NULL,
                deletion_date datetime DEFAULT NULL,
                sorting_place tinyint(4) DEFAULT NULL,
                PRIMARY KEY (link_id),
                KEY from_item_id (from_item_id),
                KEY context_id (context_id),
                KEY to_item_id (to_item_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');
    }
}
