<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170824064811 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE zzz_user
            ADD use_portal_email BOOLEAN NOT NULL DEFAULT 0;
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE zzz_user
            DROP use_portal_email;
        ');
    }
}
