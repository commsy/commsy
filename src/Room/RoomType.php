<?php

namespace App\Room;

use App\Item\ItemType;
use App\Rubric\Label\LabelType;
use App\Rubric\RubricType;

/**
 * Canonical room types understood by the deletion pipeline and the rubric
 * configuration.
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

    /**
     * The rubrics a room of this type offers until its settings say otherwise,
     * in their default order. They are also all it can offer.
     *
     * @return list<string>
     */
    public function defaultRubrics(): array
    {
        return match ($this) {
            self::Project => [
                RubricType::Announcement->value,
                RubricType::Todo->value,
                RubricType::Date->value,
                RubricType::Material->value,
                RubricType::Discussion->value,
                ItemType::User->value,
                LabelType::Group->value,
                LabelType::Topic->value,
            ],
            self::Community => [
                RubricType::Announcement->value,
                self::Project->value,
                RubricType::Todo->value,
                RubricType::Date->value,
                RubricType::Material->value,
                RubricType::Discussion->value,
                ItemType::User->value,
                LabelType::Topic->value,
            ],
            self::GroupRoom => [
                RubricType::Announcement->value,
                RubricType::Todo->value,
                RubricType::Date->value,
                RubricType::Material->value,
                RubricType::Discussion->value,
                ItemType::User->value,
                LabelType::Topic->value,
            ],
            self::PrivateRoom => [ItemType::MyRoom->value, RubricType::Date->value, ItemType::Entry->value],
            self::UserRoom => [
                RubricType::Announcement->value,
                RubricType::Todo->value,
                RubricType::Material->value,
                RubricType::Discussion->value,
                ItemType::User->value,
            ],
        };
    }
}
