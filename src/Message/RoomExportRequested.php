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

namespace App\Message;

/**
 * A room export is waiting to be built.
 *
 * Carries the row id and nothing else: how long a message sits in the queue is
 * not ours to know, and a copy of the request would go stale while it waits. The
 * handler reads the current state.
 */
readonly class RoomExportRequested
{
    public function __construct(
        private int $roomExportId,
    ) {
    }

    public function getRoomExportId(): int
    {
        return $this->roomExportId;
    }
}
