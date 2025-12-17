<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210507071808 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'change auth_source add_account to enum';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('alter table auth_source add add_account_new enum("yes", "no", "invitation") default "no" not null after `default`;');
        $this->addSql('update auth_source SET add_account_new = "yes" WHERE add_account = 1;');
        $this->addSql('alter table auth_source drop column add_account;');
        $this->addSql('alter table auth_source change add_account_new add_account enum("yes", "no", "invitation") default "no" not null;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('alter table auth_source add add_account_old tinyint default 0 not null after `default`;');
        $this->addSql('update auth_source set add_account_old = 1 WHERE add_account = "yes";');
        $this->addSql('alter table auth_source drop column add_account');
        $this->addSql('alter table auth_source change add_account_old add_account tinyint default 0 not null');
    }
}
