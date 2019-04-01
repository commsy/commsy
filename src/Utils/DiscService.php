<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;

class DiscService
{
    private $legacyEnvironment;
    private $discManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->discManager = $this->legacyEnvironment->getEnvironment()->getDiscManager();
    }

    public function copyFile ($source_file, $dest_filename, $delete_source)
    {
        return $this->discManager->copyFile($source_file, $dest_filename, $delete_source);
    }
    
    public function copyImageFromRoomToRoom ($picture_name, $new_room_id)
    {
        if ($this->discManager->copyImageFromRoomToRoom($picture_name, $new_room_id)) {
            return $this->discManager->getLastSavedFileName();
        } else {
            return false;
        }
    }
}