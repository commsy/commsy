<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170420141745 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE invitations (
                id int(11) NOT NULL AUTO_INCREMENT,
                hash varchar(255) NOT NULL,
                email varchar(255) NOT NULL,
                authsource_id int(11) NOT NULL,
                context_id int(11) NOT NULL,
                creation_date datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
                expiration_date datetime DEFAULT NULL,
                redeemed tinyint(11) NOT NULL DEFAULT \'0\',
                PRIMARY KEY (id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS invitations');
    }
}
