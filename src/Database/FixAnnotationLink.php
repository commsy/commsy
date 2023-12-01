<?php

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
