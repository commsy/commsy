<?php

namespace App\Room;

/**
 * Canonical room types understood by the deletion pipeline.
 *
 * Values mirror the legacy `cs_*_item::_type` strings (see
 * `legacy/etc/cs_constants.php` and `cs_userroom_item::ROOM_TYPE_USER`),
 * so callers that still read `items.type` from the database or hand a
 * legacy string around can map in/out without surprise.
 *
 * `portal` is intentionally **not** a room case — portals are handled
 * separately by {@see \App\Cron\Tasks\CronHardDelete} on the manager
 * level and have no {@see RoomDeleter} implementation.
 */
enum RoomType: string
{
    case Project = 'project';
    case Community = 'community';
    case GroupRoom = 'grouproom';
    case PrivateRoom = 'privateroom';
    case UserRoom = 'userroom';

    /**
     * Accepts a legacy type string and returns the matching case, or null
     * if the string does not correspond to a managed room type (e.g.
     * `portal`, `myroom`, `server`, or a rubric item type).
     */
    public static function tryFromLegacyString(string $type): ?self
    {
        return self::tryFrom($type);
    }
}
