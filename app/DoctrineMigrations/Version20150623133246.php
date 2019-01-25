<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20150623133246 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $tables = [
            'external_viewer',
            'zzz_external_viewer',
            'workflow_read',
            'zzz_workflow_read'
        ];

        foreach ($tables as $table) {
            $this->dropIndexes($table);
            $this->addPrimaryKey($table);
            $this->deleteDuplicates($table);
            $this->updatePrimaryKey($table);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }

    private function dropIndexes($tableName) {
        $schemaManager = $this->connection->getSchemaManager();

        $indexes = $schemaManager->listTableIndexes($tableName);
        foreach ($indexes as $index) {
            $this->addSql('DROP INDEX `' . $index->getName() . '` ON ' . $tableName);
        }
    }

    private function addPrimaryKey($tableName) {
        $this->addSql('ALTER TABLE ' . $tableName . ' ADD id INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY(id)');
    }

    private function deleteDuplicates($tableName) {
        $this->addSql('ALTER TABLE ' . $tableName . ' ADD INDEX tmp (item_id, user_id)');
        $this->addSql('DELETE T1 FROM ' . $tableName . ' T1 INNER JOIN ' . $tableName . ' T2 ON T1.item_id = T2.item_id AND T1.user_id = T2.user_id AND T1.id > T2.id');
        $this->addSql('ALTER TABLE ' . $tableName . ' DROP INDEX tmp');
    }

    private function updatePrimaryKey($tableName) {
        $this->addSql('ALTER TABLE ' . $tableName . ' DROP id');
        $this->addSql('ALTER TABLE ' . $tableName . ' ADD PRIMARY KEY pk_' . $tableName . ' (item_id, user_id)');
    }
}
