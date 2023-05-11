<?php

namespace App\WOPI\REST;

use App\Entity\Files;

final class WOPIFileId
{
    public static function fromCommSyFile(Files $files): string
    {
        return urlencode($files->getFilesId());
    }
}
