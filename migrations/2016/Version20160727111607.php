<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20160727111607 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('DROP TABLE IF EXISTS log_message_tag');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('
            CREATE TABLE log_message_tag (
                id int(11) NOT NULL AUTO_INCREMENT,
                tag varchar(255) NOT NULL,
                version varchar(50) NOT NULL,
                datetime datetime NOT NULL,
                language varchar(10) DEFAULT NULL,
                PRIMARY KEY (id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');
    }
}
