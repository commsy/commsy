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

namespace App\Notification;

/**
 * One room's unread entry activity, condensed into the counts the bell renders as
 * a sentence ("3 neu angelegt, 5 bearbeitet, 2 annotiert").
 *
 * Counts are distinct *entries* per action, not single events: an entry edited
 * four times counts once, so the numbers stay readable. An entry that was both
 * created and edited while unread shows up in both counts — both things happened.
 */
final readonly class RoomActivitySummary
{
    public function __construct(
        public int $contextId,
        public string $roomTitle,
        public int $created,
        public int $edited,
        public int $annotated,
        public \DateTimeImmutable $newestAt,
    ) {
    }

    /**
     * Which "!" the bell shows for this room, in the red/amber convention the
     * rubric lists and the activity panels already use: red as soon as one entry
     * is new, amber when existing entries were only changed or annotated.
     */
    public function indicatorStatus(): string
    {
        return $this->created > 0 ? 'new' : 'changed';
    }
}
