<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201106104557 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds a saved_searches table';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE saved_searches (
                id int(11) NOT NULL AUTO_INCREMENT,
                account_id int(11) NOT NULL,
                context_id int(11) NOT NULL,
                deleter_id int(11) DEFAULT NULL,
                deletion_date datetime DEFAULT NULL,
                title varchar(255) NOT NULL,
                search_url varchar(3000) NOT NULL,
                PRIMARY KEY (id),
                KEY account_id (account_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS saved_searches');
    }
}
