<?php

declare(strict_types=1);

namespace DoctrineMigrations;

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240308125018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Foreign Key Constraint between discussion and articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discussionarticles CHANGE item_id item_id INT AUTO_INCREMENT NOT NULL, CHANGE discussion_id discussion_id INT NOT NULL');
        $this->addSql('DELETE da FROM discussionarticles da LEFT JOIN discussions d ON da.discussion_id = d.item_id WHERE d.item_id IS NULL');
        $this->addSql('DELETE i FROM items i LEFT JOIN discussionarticles da ON i.item_id = da.item_id WHERE i.item_id IS NULL');
        $this->addSql('ALTER TABLE discussionarticles ADD CONSTRAINT FK_43CBF6A81ADED311 FOREIGN KEY (discussion_id) REFERENCES discussions (item_id)');
        $this->addSql('ALTER TABLE discussions CHANGE item_id item_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('CREATE INDEX IDX_8B716B63D079F553 ON discussions (modifier_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discussionarticles DROP FOREIGN KEY FK_43CBF6A81ADED311');
        $this->addSql('ALTER TABLE discussionarticles CHANGE item_id item_id INT DEFAULT 0 NOT NULL, CHANGE discussion_id discussion_id INT DEFAULT 0 NOT NULL');
        $this->addSql('DROP INDEX IDX_8B716B63D079F553 ON discussions');
        $this->addSql('ALTER TABLE discussions CHANGE item_id item_id INT DEFAULT 0 NOT NULL');
    }
}
