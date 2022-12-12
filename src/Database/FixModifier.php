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

use Symfony\Component\Console\Style\SymfonyStyle;

class FixModifier extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $tablesWithModifier = ['annotations', 'announcement', 'dates', 'discussionarticles',
            'discussions', 'labels', 'materials', 'portfolio', 'room', 'room_privat', 'section',
            'server', 'step', 'tag', 'tag2tag', 'todos', 'user', ];

        foreach ($tablesWithModifier as $tableWithModifier) {
            $sql = "
                UPDATE $tableWithModifier AS t LEFT JOIN user AS u ON t.modifier_id = u.item_id SET t.modifier_id = NULL
                WHERE t.modifier_id IS NOT NULL AND u.item_id IS NULL;
            ";
            $this->executeSQL($sql, $io);
        }

        $sql = '
            DELETE t FROM link_modifier_item AS t LEFT JOIN user AS u ON t.modifier_id = u.item_id
            WHERE t.modifier_id IS NOT NULL AND u.item_id IS NULL;
        ';
        $this->executeSQL($sql, $io);

        return true;
    }
}
