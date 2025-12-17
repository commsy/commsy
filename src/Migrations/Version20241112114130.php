<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241112114130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add oidc configuration params';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_source ADD user_info_url VARCHAR(255) DEFAULT NULL, ADD username_mapping VARCHAR(100) DEFAULT NULL, ADD email_mapping VARCHAR(100) DEFAULT NULL, ADD firstname_mapping VARCHAR(100) DEFAULT NULL, ADD lastname_mapping VARCHAR(100) DEFAULT NULL, CHANGE add_account add_account ENUM(\'yes\', \'no\', \'invitation\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_source DROP user_info_url, DROP username_mapping, DROP email_mapping, DROP firstname_mapping, DROP lastname_mapping, CHANGE add_account add_account VARCHAR(255) DEFAULT NULL');
    }
}
