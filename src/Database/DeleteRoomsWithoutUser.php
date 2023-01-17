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

class DeleteRoomsWithoutUser extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $sql = '
            DELETE items, room FROM room
            INNER JOIN items ON room.item_id = items.item_id
            LEFT JOIN user ON room.item_id = user.context_id AND user.not_deleted = 1
            WHERE user.item_id IS NULL
        ';
        $this->executeSQL($sql, $io);

        $sql = '
            DELETE items, room_privat FROM room_privat
            INNER JOIN items ON room_privat.item_id = items.item_id
            LEFT JOIN user ON room_privat.item_id = user.context_id AND user.not_deleted = 1
            WHERE user.item_id IS NULL
        ';
        $this->executeSQL($sql, $io);

        return true;
    }
}
