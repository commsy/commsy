<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops the own id counter of discussions, discussionarticles and room.
 *
 * items.item_id is the only id source; these rows reuse it. The counters came
 * in through schema diffs against entities that declared their id as
 * generated. A row inserted without an id would get a number that belongs to
 * no items row or to the one of another entry, so such an insert has to fail.
 */
final class Version20261020090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the own id counter of discussions, discussionarticles and room';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discussionarticles CHANGE item_id item_id INT NOT NULL');
        $this->addSql('ALTER TABLE discussions CHANGE item_id item_id INT NOT NULL');
        $this->addSql('ALTER TABLE room CHANGE item_id item_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room CHANGE item_id item_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE discussions CHANGE item_id item_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE discussionarticles CHANGE item_id item_id INT AUTO_INCREMENT NOT NULL');
    }
}
