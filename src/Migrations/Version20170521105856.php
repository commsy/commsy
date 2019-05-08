<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170521105856 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->removeIndex('status_2', 'room');
        $this->removeIndex('status_2', 'zzz_room');
        $this->removeIndex('room_description', 'room');
        $this->removeIndex('room_description', 'zzz_room');

        foreach ($schema->getTables() as $tableName => $table) {
            $this->addSql('ALTER TABLE ' . $tableName . ' ENGINE=InnoDB');
        }
    }

    /**
     * Removes an index after checking for existence
     *
     * @param $indexName name of the index to remove
     * @param $tableName name of the table containing the index
     */
    private function removeIndex($indexName, $tableName)
    {
        $schemaManager = $this->connection->getSchemaManager();
        $tableIndexes = $schemaManager->listTableIndexes($tableName);

        $filteredIndexes = array_filter($tableIndexes, function($index) use ($indexName) {
            return $index->getName() === $indexName;
        });

        if (!empty($filteredIndexes)) {
            $this->addSql('DROP INDEX ' . $indexName . ' ON ' . $tableName);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        foreach ($schema->getTables() as $tableName => $table) {
            $this->addSql('ALTER TABLE ' . $tableName . ' ENGINE=MyISAM');
        }

        $this->addSql('CREATE INDEX room_description ON room(room_description)');
        $this->addSql('CREATE INDEX room_description ON zzz_room(room_description)');
    }
}
