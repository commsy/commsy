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

namespace App\Files\Cleanup;

/**
 * What the file audit can find. The first three concern rows of the `files`
 * table, the others what lies in the files directory.
 */
enum FindingKind: string
{
    /** A row no entry ever got linked to, older than a day. */
    case UnlinkedRow = 'unlinked-row';

    /** A row whose links all lead to entries that are gone. */
    case DeadLinksRow = 'dead-links-row';

    /** A live row whose file is not on disk. */
    case MissingOnDisk = 'missing-on-disk';

    /** An upload or its thumbnail on disk without a row. */
    case StrayUpload = 'stray-upload';

    /** A room, user, group or portal image nothing refers to. */
    case StrayImage = 'stray-image';

    /** The directory of a room or portal that no longer exists. */
    case OrphanedDirectory = 'orphaned-directory';

    /** Something the audit does not know; reported, never touched. */
    case Unknown = 'unknown';

    public function concernsDisk(): bool
    {
        return \in_array($this, [self::StrayUpload, self::StrayImage, self::OrphanedDirectory, self::Unknown], true);
    }

    /**
     * Whether the cleanup acts on it without being asked by name. A missing
     * file is only ended when named: a storage that is not mounted makes every
     * file look missing.
     */
    public function cleanedByDefault(): bool
    {
        return !\in_array($this, [self::MissingOnDisk, self::Unknown], true);
    }

    public function description(): string
    {
        return match ($this) {
            self::UnlinkedRow => 'File rows no entry was ever linked to (older than a day)',
            self::DeadLinksRow => 'File rows whose entries are all gone',
            self::MissingOnDisk => 'File rows whose file is missing on disk',
            self::StrayUpload => 'Uploads and thumbnails on disk without a row',
            self::StrayImage => 'Room, user, group and portal images nothing refers to',
            self::OrphanedDirectory => 'Directories of rooms or portals that are gone',
            self::Unknown => 'Unknown files, reported only',
        };
    }
}
