<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190125123633 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $tables = [
            'tag',
            'zzz_tag',
        ];

        foreach ($tables as $table) {
            $columns = $schemaManager->listTableColumns($table);

            if (empty(array_filter($columns, fn($column) => $column->getName() === 'public'))) {
                $this->addSql('ALTER TABLE ' . $table . ' ADD public TINYINT NOT NULL DEFAULT 0;');
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('
            ALTER TABLE tag
            DROP public;
        ');

        $this->addSql('
            ALTER TABLE zzz_tag
            DROP public;
        ');
    }
}
