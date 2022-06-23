<?php

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

final class FixCreator extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $tablesWithCreator = ['annotations', 'announcement', 'assessments', 'dates', 'discussionarticles',
            'discussions', 'files', 'labels', 'link_items', 'materials', 'portfolio', 'room', 'room_privat', 'section',
            'server', 'step', 'tag', 'tag2tag', 'tasks', 'todos', 'user'];

        foreach ($tablesWithCreator as $tableWithCreator) {
            $sql = "
                UPDATE $tableWithCreator AS t LEFT JOIN user AS u ON t.creator_id = u.item_id SET t.creator_id = NULL
                WHERE t.creator_id IS NOT NULL AND u.item_id IS NULL;
            ";
            $this->executeSQL($sql, $io);
        }

        return true;
    }
}