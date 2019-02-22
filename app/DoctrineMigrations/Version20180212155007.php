<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180212155007 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE IF NOT EXISTS translation (
                id int(11) NOT NULL AUTO_INCREMENT,
                context_id int(11) NOT NULL,
                translation_key varchar(255) NOT NULL,
                translation_de varchar(255) NOT NULL,
                translation_en varchar(255) NOT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ');

        $dQueryBuilder = $this->connection->createQueryBuilder();

        $qb = $dQueryBuilder
            ->select('p.item_id')
            ->from('portal', 'p');
        $portals = $qb->execute();

        foreach ($portals as $portal) {
            $this->addSql('
                INSERT INTO translation (id, context_id, translation_key, translation_de, translation_en)
                VALUES (DEFAULT, "'.$portal['item_id'].'", "EMAIL_REGEX_ERROR", "Die angegebene E-Mail-Adresse entspricht nicht den Vorgaben der Portalmoderation.", "The given email-address does not match the requirements set by the portal moderators.")
            ');
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS translation');
    }
}
