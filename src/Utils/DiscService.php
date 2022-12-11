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

class DiscService
{
    private $discManager;

    public function __construct(private LegacyEnvironment $legacyEnvironment)
    {
        $this->discManager = $this->legacyEnvironment->getEnvironment()->getDiscManager();
    }

    public function copyFile($source_file, $dest_filename, $delete_source)
    {
        return $this->discManager->copyFile($source_file, $dest_filename, $delete_source);
    }

    public function copyImageFromRoomToRoom($picture_name, $new_room_id)
    {
        if ($this->discManager->copyImageFromRoomToRoom($picture_name, $new_room_id)) {
            return $this->discManager->getLastSavedFileName();
        } else {
            return false;
        }
    }
}
