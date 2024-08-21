<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240814112708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add oidc auth source columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_source ADD issuer VARCHAR(255) DEFAULT NULL, ADD client_identifier VARCHAR(255) DEFAULT NULL, ADD client_secret VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_source DROP issuer, DROP client_identifier, DROP client_secret');
    }
}
