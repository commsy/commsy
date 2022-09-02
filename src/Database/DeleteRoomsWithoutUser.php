<?php

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteRoomsWithoutUser extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $sql = "
            DELETE items, room FROM room
            INNER JOIN items ON room.item_id = items.item_id
            LEFT JOIN user ON room.item_id = user.context_id AND user.not_deleted = 1
            WHERE user.item_id IS NULL
        ";
        $this->executeSQL($sql, $io);

        $sql = "
            DELETE items, room_privat FROM room_privat
            INNER JOIN items ON room_privat.item_id = items.item_id
            LEFT JOIN user ON room_privat.item_id = user.context_id AND user.not_deleted = 1
            WHERE user.item_id IS NULL
        ";
        $this->executeSQL($sql, $io);

        $sql = "
            DELETE items, zzz_room FROM zzz_room
            INNER JOIN items ON zzz_room.item_id = items.item_id
            LEFT JOIN user ON zzz_room.item_id = user.context_id AND user.not_deleted = 1
            WHERE user.item_id IS NULL
        ";
        $this->executeSQL($sql, $io);

        return true;
    }
}
