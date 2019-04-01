<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170721185631 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            ALTER TABLE hash
            ADD caldav char(32) NULL;
        ');

        $this->addSql('CREATE INDEX caldav ON hash(caldav)');

        $this->addSql('
            ALTER TABLE zzz_hash
            ADD caldav char(32) NULL;
        ');

        $this->addSql('CREATE INDEX caldav ON zzz_hash(caldav)');

        $this->addSql('
            ALTER TABLE calendars
            ADD synctoken INT(11) NOT NULL DEFAULT 0;
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('
            ALTER TABLE hash
            DROP caldav;
        ');

        $this->addSql('
            ALTER TABLE zzz_hash
            DROP caldav;
        ');

        $this->addSql('
            ALTER TABLE calendars
            DROP synctoken;
        ');
    }
}
