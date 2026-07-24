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

declare(strict_types=1);

namespace App\Item;

/**
 * `items.type` discriminator values that do not have a dedicated domain enum
 * of their own.
 *
 * The possible values of the `items.type` column are spread across several
 * typed enums rather than one catch-all:
 *   - rubric types    → {@see \App\Rubric\RubricType}
 *   - room types      → {@see \App\Room\RoomType}
 *   - label subtypes  → {@see \App\Rubric\Label\LabelType} (stored in `labels.type`)
 *   - everything else → this enum
 *
 * Values mirror the legacy `CS_*_TYPE` strings, so callers that read
 * `$item->getItemType()` map in/out without surprise.
 */
enum ItemType: string
{
    case Item = 'item';
    case User = 'user';
    case Step = 'step';
    case Section = 'section';
    case DiscussionArticle = 'discarticle';
    case Task = 'task';
    case File = 'file';
    case Tag = 'tag';
    case Tag2Tag = 'tag2tag';
    case Link = 'link';
    case LinkItem = 'link_item';
    case LinkModifierItem = 'link_modifier_item';
    case LinkItemFile = 'link_item_file';
    case Noticed = 'noticed';
    case Time = 'time';
    case Entry = 'entry';
    case Assessment = 'assessments';
    case Room = 'room';
    case MyRoom = 'myroom';
    case Server = 'server';

    /**
     * Accepts a legacy type string and returns the matching case, or null if
     * the string is not one of these discriminators (e.g. a rubric, room or
     * label-subtype value handled by the dedicated enums).
     */
    public static function tryFromLegacyString(string $type): ?self
    {
        return self::tryFrom($type);
    }
}
