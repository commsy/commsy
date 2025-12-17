<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20160727100551 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('DROP TABLE IF EXISTS log_ads');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('
            CREATE TABLE log_ads (
                id int(11) NOT NULL AUTO_INCREMENT,
                cid int(11) DEFAULT NULL,
                aim varchar(255) NOT NULL,
                timestamp timestamp NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY cid (cid),
                KEY timestamp (timestamp)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');
    }
}
