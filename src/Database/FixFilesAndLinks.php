<?php

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

final class FixFilesAndLinks extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        // Delete links without files
        $sql = '
            DELETE ilf FROM item_link_file AS ilf
            LEFT JOIN files AS f ON ilf.file_id = f.files_id
            WHERE f.files_id IS NULL
        ';
        $this->executeSQL($sql, $io);

        // Delete files without links
        $sql = '
            DELETE f FROM files AS f
            LEFT JOIN item_link_file AS ilf ON f.files_id = ilf.file_id
            WHERE ilf.file_id IS NULL
        ';
        $this->executeSQL($sql, $io);

        return true;
    }
}
