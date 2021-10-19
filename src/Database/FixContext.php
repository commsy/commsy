<?php

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

class FixContext extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $tablesWithContext = ['annotations', 'announcement', 'assessments', 'calendars', 'dates',
            'discussionarticles', 'discussions', 'files', 'invitations', 'labels', 'licenses', 'link_items',
            'links', 'materials', 'room', 'room_privat', 'section', 'step', 'tag', 'tag2tag', 'tasks',
            'terms', 'todos', 'translation',  'user'];
        // TODO: Check auth_source

        foreach ($tablesWithContext as $tableWithContext) {
            $sql = "
                DELETE t FROM $tableWithContext AS t
                LEFT JOIN room AS c1 ON t.context_id = c1.item_id
                LEFT JOIN zzz_room AS c2 ON t.context_id = c2.item_id
                LEFT JOIN room_privat AS c3 ON t.context_id = c3.item_id
                LEFT JOIN portal AS c4 ON t.context_id = c4.id
                LEFT JOIN server AS c5 ON t.context_id = c5.item_id
                WHERE t.context_id IS NOT NULL
                AND c1.item_id IS NULL AND c2.item_id IS NULL AND c3.item_id IS NULL AND c4.id IS NULL AND c5.item_id IS NULL;
            ";
            $this->executeSQL($sql, $io);
        }

        $sql = "
            DELETE t FROM items AS t
            LEFT JOIN room AS c1 ON t.context_id = c1.item_id
            LEFT JOIN zzz_room AS c2 ON t.context_id = c2.item_id
            LEFT JOIN room_privat AS c3 ON t.context_id = c3.item_id
            LEFT JOIN portal AS c4 ON t.context_id = c4.id
            LEFT JOIN server AS c5 ON t.context_id = c5.item_id
            WHERE t.context_id IS NOT NULL AND t.type != 'server'
            AND c1.item_id IS NULL AND c2.item_id IS NULL AND c3.item_id IS NULL AND c4.id IS NULL AND c5.item_id IS NULL;
        ";
        $this->executeSQL($sql, $io);

        return true;
    }
}