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

class FixAnnotationLink extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $sql = '
            DELETE t FROM annotations AS t
            LEFT JOIN items AS i ON t.linked_item_id = i.item_id
            WHERE i.item_id IS NULL;
        ';
        $this->executeSQL($sql, $io);

        return true;
    }
}
