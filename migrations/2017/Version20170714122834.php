<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170714122834 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('
            ALTER TABLE user
            ADD use_portal_email BOOLEAN NOT NULL DEFAULT 0;
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('
            ALTER TABLE user
            DROP use_portal_email;
        ');
    }
}
