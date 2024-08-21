<?php

namespace App\Room;

enum RoomStatus: int
{
    case NO_ROOM = 0;
    case OPEN = 1;
    case CLOSED = 2;
    case LOCKED = 3;

    case LOCKED_PORTAL_MOD = 4;
}
