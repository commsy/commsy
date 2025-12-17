<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210406110145 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add guest enum option to auth_source type   ';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('alter table auth_source modify column type enum(\'local\', \'oidc\', \'ldap\', \'shib\', \'guest\') default \'local\';)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('alter table auth_source modify column type enum(\'local\', \'oidc\', \'ldap\', \'shib\') default \'local\';');
    }
}
