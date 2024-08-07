<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180227100813 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('
            CREATE TABLE terms (
                id int(11) NOT NULL AUTO_INCREMENT,
                context_id int(11) NOT NULL,
                title varchar(255) NOT NULL,
                content_de text NOT NULL,
                content_en text NOT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE IF EXISTS terms');
    }
}
