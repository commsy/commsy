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

namespace App\Rubric\Label;

/**
 * Label subtypes stored in the `labels.type` column.
 *
 * All of these share `items.type = 'label'` (see {@see \App\Rubric\RubricType::Label});
 * this enum models the subtype discriminator that distinguishes a group from
 * a topic, institution or buzzword (hashtag).
 *
 * Values mirror the legacy `CS_GROUP_TYPE` / `CS_TOPIC_TYPE` /
 * `CS_INSTITUTION_TYPE` / `CS_BUZZWORD_TYPE` strings, so callers that read
 * `$label->getLabelType()` map in/out without surprise.
 */
enum LabelType: string
{
    case Group = 'group';
    case Topic = 'topic';
    case Institution = 'institution';
    case Buzzword = 'buzzword';

    /**
     * Accepts a legacy label-type string and returns the matching case, or
     * null if the string is not a known label subtype.
     */
    public static function tryFromLegacyString(string $type): ?self
    {
        return self::tryFrom($type);
    }
}
