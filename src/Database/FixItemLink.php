<?php

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deletes entries missing a link to or from the items table
 */
class FixItemLink extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $tablesWithItemLinks = ['annotations', 'announcement', 'dates', 'discussionarticles', 'discussions', 'labels',
            'link_items', 'materials', 'portfolio', 'room', 'room_privat', 'section', 'server', 'step', 'tag', 'tasks',
            'todos', 'user'];

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
        $sql = "SELECT type FROM items GROUP BY type";
        $stmt = $this->executeSQL($sql, $io);
        $types = array_column($stmt->fetchAllAssociative(), 'type');

        // key => type
        // value => table name
        $mapping = [
            'annotation' => 'annotations',
            'announcement' => 'announcement',
            'assessments' => 'assessments',
            'community' => 'room',
            'date' => 'dates',
            'discarticle' => 'discussionarticles',
            'discussion' => 'discussions',
            'grouproom' => 'room',
            'label' => 'labels',
            'link_item' => 'link_items',
            'material' => 'materials',
            'portfolio' => 'portfolio',
            'privateroom' => 'room_privat',
            'project' => 'room',
            'section' => 'section',
            'server' => 'server',
            'step' => 'step',
            'tag' => 'tag',
            'task' => 'tasks',
            'todo' => 'todos',
            'user' => 'user',
            'userroom' => 'room',
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