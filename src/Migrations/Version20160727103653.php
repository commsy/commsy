<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20160727103653 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS search_index');
        $this->addSql('DROP TABLE IF EXISTS search_time');
        $this->addSql('DROP TABLE IF EXISTS search_word');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE search_index (
                si_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                si_sw_id mediumint(8) unsigned NOT NULL DEFAULT \'0\',
                si_item_id int(11) NOT NULL DEFAULT \'0\',
                si_item_type varchar(15) NOT NULL,
                si_count smallint(5) unsigned NOT NULL DEFAULT \'0\',
                PRIMARY KEY (si_id),
                UNIQUE KEY un_si_sw_id (si_item_id,si_sw_id,si_item_type),
                KEY si_sw_id (si_sw_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');

        $this->addSql('
            CREATE TABLE search_time (
                st_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                st_item_id int(11) NOT NULL DEFAULT \'0\',
                st_date datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                PRIMARY KEY (st_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');

        $this->addSql('
            CREATE TABLE search_word (
                sw_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                sw_word varchar(32) NOT NULL DEFAULT \'\',
                sw_lang varchar(5) NOT NULL,
                PRIMARY KEY (sw_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ');
    }
}
