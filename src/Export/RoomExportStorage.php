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

use App\Entity\RoomExport;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Where a room's export archive lives.
 *
 * Under `files/temp`, which the cleanup cron sweeps by age. An archive holds a
 * whole room's content, so it is meant to expire rather than to be kept: the
 * download window is what it is for, not storage.
 *
 * The file name follows from the row, so a lost row means a file nobody can
 * claim and the sweep collects it.
 */
final readonly class RoomExportStorage
{
    public function __construct(
        #[Autowire('%files_directory%/temp/room_export')]
        private string $baseDirectory,
    ) {
    }

    public function filenameFor(RoomExport $export): string
    {
        return sprintf('room-%d-export-%d.zip', $export->getRoom()->getItemId(), $export->getId());
    }

    public function directoryFor(RoomExport $export): string
    {
        return $this->baseDirectory.'/'.$export->getRoom()->getItemId();
    }

    /**
     * The path the archive would have. Says nothing about whether it is there —
     * ask {@see self::exists()} for that.
     */
    public function pathFor(RoomExport $export): string
    {
        return $this->directoryFor($export).'/'.($export->getFilename() ?? $this->filenameFor($export));
    }

    public function exists(RoomExport $export): bool
    {
        return null !== $export->getFilename() && is_file($this->pathFor($export));
    }
}
