<?php

namespace App\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

final class DeleteFilesWithoutLink extends GeneralCheck
{
    public function resolve(SymfonyStyle $io): bool
    {
        $sql = '
            DELETE item_link_file, files FROM files
            LEFT JOIN item_link_file ON files.files_id = item_link_file.file_id
            WHERE item_link_file.file_id IS NULL
        ';
        $this->executeSQL($sql, $io);

        return true;
    }
}
