<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230126104901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unnecessary extras / columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE files DROP COLUMN temp_upload_session_id;');
        $this->addSql('ALTER TABLE files DROP COLUMN has_html;');
        $this->addSql('ALTER TABLE files DROP COLUMN scan;');

        DbConverter::removeExtra($this->connection, 'files', 'files_id', [
            'WORDPRESS_POST_ID',
            'SCRIBD_DOC_ID',
            'SCRIBD_ACCESS_KEY'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
