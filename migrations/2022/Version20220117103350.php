<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220117103350 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adding Idp to Shibboleth authentication';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE id_p (id TINYINT(11) AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL,url VARCHAR(255) NOT NULL, auth_source_shibboleth_id TINYINT(11) NOT NULL, PRIMARY KEY(id))');

    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE id_p');

    }
}
