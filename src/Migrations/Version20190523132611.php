<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190523132611 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `translation` MODIFY translation_de VARCHAR(2000) NOT NULL;');
        $this->addSql('ALTER TABLE `translation` MODIFY translation_en VARCHAR(2000) NOT NULL;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `translation` MODIFY translation_de VARCHAR(255) NOT NULL;');
        $this->addSql('ALTER TABLE `translation` MODIFY translation_en VARCHAR(255) NOT NULL;');
    }
}
