<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191007171054 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Update portal table and items';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('alter table portal change item_id id int auto_increment;');
        $this->addSql('alter table portal drop column modifier_id;');
        $this->addSql('alter table portal alter column creation_date drop default;');
        $this->addSql('alter table portal alter column modification_date drop default;');
        $this->addSql('drop index context_id on portal;');
        $this->addSql('alter table portal drop column context_id;');
        $this->addSql('drop index creator_id on portal;');
        $this->addSql('alter table portal drop column creator_id;');
        $this->addSql('delete from items where items.type = \'portal\'');
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
