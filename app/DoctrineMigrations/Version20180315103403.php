<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180315103403 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE licenses (
                id int(11) NOT NULL AUTO_INCREMENT,
                context_id int(11) NOT NULL,
                title varchar(255) NOT NULL,
                content text NOT NULL,
                position int(11) NOT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ');

        $this->addSql('ALTER TABLE materials ADD license_id int(11) NULL');
        $this->addSql('ALTER TABLE zzz_materials ADD license_id int(11) NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS licenses');

        $this->addSql('ALTER TABLE materials DROP COLUMN license_id');
        $this->addSql('ALTER TABLE zzz_materials DROP COLUMN license_id');
    }
}
