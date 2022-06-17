<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220414210351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add CommSy icon link to server';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server ADD commsy_icon_link VARCHAR(255) NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server DROP COLUMN commsy_icon_link');
    }
}
