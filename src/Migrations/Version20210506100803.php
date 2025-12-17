<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210506100803 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Modify indices on user table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS user_context_id_index ON user (context_id);');
        $this->addSql('DROP INDEX IF EXISTS deleter_id ON user;');
        $this->addSql('DROP INDEX IF EXISTS deletion_date ON user;');
        $this->addSql('CREATE INDEX IF NOT EXISTS user_deleter_id_deletion_date_index ON user (deleter_id, deletion_date);');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP INDEX IF EXISTS user_deleter_id_deletion_date_index ON user;');
        $this->addSql('CREATE INDEX IF NOT EXISTS deleter_id ON user (deleter_id);');
        $this->addSql('CREATE INDEX IF NOT EXISTS deletion_date ON user (deletion_date);');
        $this->addSql('DROP INDEX IF EXISTS user_context_id_index ON user;');
    }
}
