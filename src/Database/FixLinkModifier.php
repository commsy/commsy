<?php

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

class FixLinkModifier extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $sql = "
            DELETE t FROM link_modifier_item AS t
            LEFT JOIN user AS u ON t.modifier_id = u.item_id
            WHERE t.modifier_id IS NOT NULL AND u.item_id IS NULL;
        ";
        $this->executeSQL($sql, $io);

        return true;
    }
}