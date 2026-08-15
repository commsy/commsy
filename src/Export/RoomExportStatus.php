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

namespace App\Export;

/**
 * How far an export has got.
 *
 * There is no state for "the file is gone": whether an archive can still be
 * downloaded follows from the file being there, and the cleanup that removes it
 * knows nothing about these rows. A finished export whose file has expired
 * therefore stays `Finished` and says so at download time — the row remains the
 * record that the export happened.
 */
enum RoomExportStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Finished = 'finished';
    case Failed = 'failed';

    /**
     * Waiting for a worker or in the hands of one — either way a second export
     * of the same room must not start.
     */
    public function isInProgress(): bool
    {
        return self::Pending === $this || self::Running === $this;
    }
}
