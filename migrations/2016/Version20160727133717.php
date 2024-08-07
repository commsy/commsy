<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20160727133717 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('DROP TABLE IF EXISTS file_multi_upload');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('
            CREATE TABLE file_multi_upload (
                id int(11) NOT NULL AUTO_INCREMENT,
                session_id varchar(150) NOT NULL,
                file_array text NOT NULL,
                cid int(11) DEFAULT NULL,
                PRIMARY KEY (id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');
    }
}
