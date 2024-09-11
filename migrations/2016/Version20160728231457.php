<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20160728231457 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('DROP TABLE IF EXISTS log_error');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('
            CREATE TABLE log_error (
                id int(11) NOT NULL AUTO_INCREMENT,
                datetime datetime NOT NULL,
                number int(11) NOT NULL,
                type varchar(255) NOT NULL,
                message mediumtext NOT NULL,
                url varchar(255) DEFAULT NULL,
                referer varchar(255) DEFAULT NULL,
                file varchar(255) NOT NULL,
                line int(11) NOT NULL,
                context int(11) NOT NULL,
                module varchar(255) NOT NULL,
                function varchar(255) NOT NULL,
                user varchar(255) NOT NULL,
                PRIMARY KEY (id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');
    }
}
