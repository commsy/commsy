<?php

namespace App\Room\Copy;

use cs_room_item;
use cs_user_item;

interface CopyStrategy
{
    public function copySettings(cs_room_item $source, cs_room_item $target): void;

    public function copyData(cs_room_item $source, cs_room_item $target, cs_user_item $creator): void;
}