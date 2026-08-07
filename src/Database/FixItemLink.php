<?php

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

namespace App\Database;

use App\Item\ItemType;
use App\Room\RoomType;
use App\Rubric\RubricType;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deletes entries missing a link to or from the items table.
 */
class FixItemLink extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $tablesWithItemLinks = ['annotations', 'announcement', 'assessments', 'dates', 'discussionarticles',
            'discussions', 'labels', 'link_items', 'materials', 'room', 'section', 'server', 'step', 'tag', 'tasks',
            'todos', 'user', ];

        foreach ($tablesWithItemLinks as $tablesWithItemLink) {
            $sql = "
                DELETE t FROM $tablesWithItemLink AS t
                LEFT JOIN items AS i ON t.item_id = i.item_id
                WHERE i.item_id IS NULL;
            ";
            $this->executeSQL($sql, $io);
        }

        $sql = "DELETE FROM items WHERE type = ''";
        $this->executeSQL($sql, $io);

        // Collect all types in the item table
        $sql = 'SELECT type FROM items GROUP BY type';
        $stmt = $this->executeSQL($sql, $io);
        $types = array_column($stmt->fetchAllAssociative(), 'type');

        // items.type => the table holding that type's own row. Keys come from
        // the enums that own the discriminator values; the table names are
        // only known here.
        $mapping = [
            RubricType::Annotation->value => 'annotations',
            RubricType::Announcement->value => 'announcement',
            ItemType::Assessment->value => 'assessments',
            RoomType::Community->value => 'room',
            RubricType::Date->value => 'dates',
            ItemType::DiscussionArticle->value => 'discussionarticles',
            RubricType::Discussion->value => 'discussions',
            RoomType::GroupRoom->value => 'room',
            RubricType::Label->value => 'labels',
            ItemType::LinkItem->value => 'link_items',
            RubricType::Material->value => 'materials',
            RoomType::PrivateRoom->value => 'room',
            RoomType::Project->value => 'room',
            ItemType::Section->value => 'section',
            ItemType::Server->value => 'server',
            ItemType::Step->value => 'step',
            ItemType::Tag->value => 'tag',
            ItemType::Task->value => 'tasks',
            RubricType::Todo->value => 'todos',
            ItemType::User->value => 'user',
            RoomType::UserRoom->value => 'room',
        ];
        foreach ($types as $type) {
            if (!isset($mapping[$type])) {
                $io->warning("Missing mapping for type $type");
                continue;
            }

            $sql = "
                DELETE i FROM items AS i
                LEFT JOIN $mapping[$type] AS t ON i.item_id = t.item_id
                WHERE i.type = '$type' AND t.item_id IS NULL;
            ";
            $this->executeSQL($sql, $io);
        }

        return true;
    }
}
