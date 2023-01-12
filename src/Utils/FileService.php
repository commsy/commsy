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

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_file_item;

class FileService
{
    public function __construct(private LegacyEnvironment $legacyEnvironment)
    {
    }

    public function getFile($fileId): ?cs_file_item
    {
        $fileManager = $this->legacyEnvironment->getEnvironment()->getFileManager();

        return $fileManager->getItem($fileId);
    }

    public function getNewFile()
    {
        $fileManager = $this->legacyEnvironment->getEnvironment()->getFileManager();

        return $fileManager->getNewItem();
    }
}
