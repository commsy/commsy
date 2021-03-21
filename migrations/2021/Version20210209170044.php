<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210209170044 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remove redeemed column from invitations and truncate';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('alter table invitations drop column redeemed;');
        $this->addSql('truncate table invitations');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('alter table invitations add redeemed tinyint(11) NOT NULL DEFAULT "0";');
    }
}
