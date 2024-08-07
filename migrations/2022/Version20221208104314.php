<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221208104314 extends AbstractMigration
{
    private const TABLES_NEED_FLAG = [];

    public function getDescription(): string
    {
        return 'Convert archived rooms to flag';
    }

    public function up(Schema $schema): void
    {
        // Add a new archive flag to room table
        $this->addSql('ALTER TABLE room ADD archived bool DEFAULT FALSE NOT NULL AFTER status;');

        // Move all entries from zzz_room
        $this->addSql('ALTER TABLE room ALTER COLUMN archived SET DEFAULT TRUE;');
        $this->addSql($this->getMoveSql('zzz_room', 'room'));
        $this->addSql('ALTER TABLE room ALTER COLUMN archived SET DEFAULT FALSE;');
        $this->addSql('DROP TABLE zzz_room;');

        // Consider rooms with status = 2 as archived
        $this->addSql('UPDATE room SET archived = true, status = 1 WHERE status = 2;');

        // Move all entries from other tables
        $this->addSql($this->getMoveSql('zzz_annotations', 'annotations'));
        $this->addSql('DROP TABLE zzz_annotations;');

        $this->addSql($this->getMoveSql('zzz_announcement', 'announcement'));
        $this->addSql('DROP TABLE zzz_announcement;');

        $this->addSql($this->getMoveSql('zzz_assessments', 'assessments'));
        $this->addSql('DROP TABLE zzz_assessments;');

        $this->addSql($this->getMoveSql('zzz_calendars', 'calendars'));
        $this->addSql('DROP TABLE zzz_calendars;');

        $this->addSql($this->getMoveSql('zzz_dates', 'dates'));
        $this->addSql('DROP TABLE zzz_dates;');

        $this->addSql($this->getMoveSql('zzz_discussionarticles', 'discussionarticles'));
        $this->addSql('DROP TABLE zzz_discussionarticles;');

        $this->addSql($this->getMoveSql('zzz_discussions', 'discussions'));
        $this->addSql('DROP TABLE zzz_discussions;');

        $this->addSql($this->getMoveSql('zzz_external_viewer', 'external_viewer'));
        $this->addSql('DROP TABLE zzz_external_viewer;');

        $this->addSql($this->getMoveSql('zzz_files', 'files'));
        $this->addSql('DROP TABLE zzz_files;');

        $this->addSql($this->getMoveSql('zzz_hash', 'hash'));
        $this->addSql('DROP TABLE zzz_hash;');

        $this->addSql($this->getMoveSql('zzz_item_link_file', 'item_link_file'));
        $this->addSql('DROP TABLE zzz_item_link_file;');

        $this->addSql($this->getMoveSql('zzz_items', 'items'));
        $this->addSql('DROP TABLE zzz_items;');

        $this->addSql($this->getMoveSql('zzz_labels', 'labels'));
        $this->addSql('DROP TABLE zzz_labels;');

        $this->addSql($this->getMoveSql('zzz_link_items', 'link_items'));
        $this->addSql('DROP TABLE zzz_link_items;');

        $this->addSql($this->getMoveSql('zzz_link_modifier_item', 'link_modifier_item'));
        $this->addSql('DROP TABLE zzz_link_modifier_item;');

        $this->addSql($this->getMoveSql('zzz_links', 'links'));
        $this->addSql('DROP TABLE zzz_links;');

        $this->addSql($this->getMoveSql('zzz_materials', 'materials'));
        $this->addSql('DROP TABLE zzz_materials;');

        $this->addSql($this->getMoveSql('zzz_noticed', 'noticed'));
        $this->addSql('DROP TABLE zzz_noticed;');

        $this->addSql($this->getMoveSql('zzz_reader', 'reader'));
        $this->addSql('DROP TABLE zzz_reader;');

        $this->addSql($this->getMoveSql('zzz_section', 'section'));
        $this->addSql('DROP TABLE zzz_section;');

        $this->addSql($this->getMoveSql('zzz_step', 'step'));
        $this->addSql('DROP TABLE zzz_step;');

        $this->addSql($this->getMoveSql('zzz_tag', 'tag'));
        $this->addSql('DROP TABLE zzz_tag;');

        $this->addSql($this->getMoveSql('zzz_tag2tag', 'tag2tag'));
        $this->addSql('DROP TABLE zzz_tag2tag;');

        $this->addSql($this->getMoveSql('zzz_tasks', 'tasks'));
        $this->addSql('DROP TABLE zzz_tasks;');

        $this->addSql($this->getMoveSql('zzz_todos', 'todos'));
        $this->addSql('DROP TABLE zzz_todos;');

        $this->addSql($this->getMoveSql('zzz_user', 'user'));
        $this->addSql('DROP TABLE zzz_user;');

        $this->addSql($this->getMoveSql('zzz_workflow_read', 'workflow_read'));
        $this->addSql('DROP TABLE zzz_workflow_read;');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }

    private function getMoveSql(string $srcTableName, string $destTableName): string
    {
        $sm = $this->connection->createSchemaManager();
        $srcTable = $sm->introspectTable($srcTableName);

        $srcColumns = implode(', ', array_values(array_map(fn(Column $column) => "$srcTableName." . $column->getName(), $srcTable->getColumns())));
        $columns = implode(', ', array_values(array_map(fn(Column $column) => $column->getName(), $srcTable->getColumns())));

        return "INSERT IGNORE INTO $destTableName ($columns) SELECT $srcColumns FROM $srcTableName";
    }
}
