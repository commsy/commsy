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

namespace App\Cron\Tasks;

use App\Files\FileDeleter;
use App\Legacy\LegacyAuxHardDeleter;
use App\Room\RoomHardDeleter;
use App\Rubric\RubricHardDeleter;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class CronHardDelete implements CronTaskInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private RoomHardDeleter $roomHardDeleter,
        private RubricHardDeleter $rubricHardDeleter,
        private LegacyAuxHardDeleter $legacyAuxHardDeleter,
        private FileDeleter $fileDeleter,
    ) {
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        // Auxiliary tables that no RubricDeleter / RoomDeleter owns:
        // shared `items` twin rows, `link_items` (covers both legacy
        // CS_LINK_TYPE + CS_LINKITEM_TYPE, same table), tag pivots,
        // `tasks` (room-scope, not a rubric). These are handled by
        // {@see \App\Legacy\LegacyAuxHardDeleter}.
        //
        // Files are special: `files` + `item_link_file` carry filesystem
        // side effects and FK cleanup, so they get their own path via
        // {@see \App\Files\FileDeleter::hardDeleteExpiredFiles()}.
        //
        // Rubric-primary types (Announcement, Annotation, Date,
        // Discussion, Label, Material, Todo) go through
        // {@see RubricHardDeleter} — it iterates the registered
        // `app.rubric.deleter` services so each rubric owns its own
        // physical-cleanup SQL, including sub-entry tables
        // (`section` / `step` / `discussionarticles`).
        //
        // CS_ROOM_TYPE / CS_PORTAL_TYPE sit on their own RoomHardDeleter
        // paths further down.
        //
        // CS_USER_TYPE stays off this path: user items are hard-deleted
        // either proactively by {@see \App\Account\AccountDeleter} or as
        // part of the room cascade inside {@see RoomHardDeleter}. See
        // {@see LegacyAuxHardDeleter::hardDeleteItemsRows()} for the
        // accompanying `type != 'user'` safety filter on the shared
        // `items` sweep.

        $deleteDays = $this->parameterBag->get('commsy.settings.delete_days');
        if (empty($deleteDays) || !is_numeric($deleteDays)) {
            return;
        }
        $deleteDays = (int) $deleteDays;

        // Rubric primary types: each registered RubricDeleter sweeps its
        // own tables (including owned sub-entries).
        $this->rubricHardDeleter->hardDeleteOlderThan($deleteDays);

        // Files first — FK constraint: item_link_file references files,
        // and the filesystem cleanup needs the row data. The legacy loop
        // did link_item_file before files for the same reason; this
        // method folds both halves into a single transaction-friendly
        // sweep.
        $this->fileDeleter->hardDeleteExpiredFiles($deleteDays);

        // Remaining aux tables (items / link_items / tag / tag2tag /
        // tasks) — pure SQL DELETE against the soft-delete cutoff.
        $this->legacyAuxHardDeleter->hardDeleteLinkItemRows($deleteDays);
        $this->legacyAuxHardDeleter->hardDeleteTagRows($deleteDays);
        $this->legacyAuxHardDeleter->hardDeleteTag2TagPivotRows($deleteDays);
        $this->legacyAuxHardDeleter->hardDeleteTaskRows($deleteDays);
        // `items` last: its rows are referenced by every rubric row above
        // (the shared twin), so sweep it after everything that might
        // still need a lookup-by-item_id has already drained.
        $this->legacyAuxHardDeleter->hardDeleteItemsRows($deleteDays);

        // Rooms: one cascade per soft-deleted room, dispatched by type
        // through the RoomDeleter registry.
        $this->roomHardDeleter->hardDeleteRoomsOlderThan($deleteDays);

        // Portals: non-cascading DELETE on the `portal` table. The
        // previous "not implemented yet" comment noted the concern that
        // a portal hard-delete would orphan its rooms — the concern is
        // resolved by scope: a soft-deleted portal's rooms carry their
        // own deletion_date and are already purged by the step above.
        // Legacy never actually ran this branch (the CS_PORTAL_TYPE
        // constant is not defined anywhere in legacy/etc/cs_constants.php),
        // so this is the first time portals are physically removed at
        // all.
        $this->roomHardDeleter->hardDeletePortalsOlderThan($deleteDays);
    }

    public function getSummary(): string
    {
        return 'Finally delete soft deleted items';
    }
}
