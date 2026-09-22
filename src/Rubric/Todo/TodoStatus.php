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

namespace App\Rubric\Todo;

/**
 * The three statuses every task has, whatever room it is in.
 *
 * This is deliberately *not* the type of the `status` column: a room may add
 * statuses of its own, which it stores as further numbers together with the
 * wording it chose for them (see cs_context_item::getExtraToDoStatusArray()).
 * Typing the column would make Doctrine refuse to load those tasks at all, so
 * the column stays an int and this enum names the part of it that is fixed —
 * {@see self::tryFrom()} answers null for a status only the room can explain.
 */
enum TodoStatus: int
{
    case NotStarted = 1;
    case InProgress = 2;
    case Done = 3;

    /**
     * The name this status carries outside CommSy, in an export for instance.
     * Fixed rather than translated: a document should not bake in the language
     * a room happens to be read in.
     */
    public function slug(): string
    {
        return match ($this) {
            self::NotStarted => 'not_started',
            self::InProgress => 'in_progress',
            self::Done => 'done',
        };
    }
}
