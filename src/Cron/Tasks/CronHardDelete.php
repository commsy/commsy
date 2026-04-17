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

use App\Room\RoomHardDeleter;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class CronHardDelete implements CronTaskInterface
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private ParameterBagInterface $parameterBag,
        private RoomHardDeleter $roomHardDeleter,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        // Rubric-level item types — these are independent of any
        // containing room (orphaned items past threshold, legacy cleanup
        // for rows that were never reached via a room hard-delete) and
        // are still handled by the legacy per-manager `deleteReallyOlderThan`.
        //
        // CS_ROOM_TYPE was previously in this list but has moved to the
        // dedicated RoomHardDeleter path below — it runs a single
        // bookkeeping cascade (file system, reader, hashes, 20 rubric
        // tables, room row) per soft-deleted room via the registered
        // RoomDeleter implementations instead of an undifferentiated
        // room-manager pass.
        $itemTypes = [];
        $itemTypes[] = CS_ANNOTATION_TYPE;
        $itemTypes[] = CS_ANNOUNCEMENT_TYPE;
        $itemTypes[] = CS_DATE_TYPE;
        $itemTypes[] = CS_DISCUSSION_TYPE;
        $itemTypes[] = CS_LINKITEMFILE_TYPE;
        $itemTypes[] = CS_FILE_TYPE;
        $itemTypes[] = CS_ITEM_TYPE;
        $itemTypes[] = CS_LABEL_TYPE;
        $itemTypes[] = CS_LINK_TYPE;
        $itemTypes[] = CS_LINKITEM_TYPE;
        $itemTypes[] = CS_MATERIAL_TYPE;
        $itemTypes[] = CS_SECTION_TYPE;
        $itemTypes[] = CS_TAG_TYPE;
        $itemTypes[] = CS_TAG2TAG_TYPE;
        $itemTypes[] = CS_TASK_TYPE;
        $itemTypes[] = CS_TODO_TYPE;

        // CS_USER_TYPE is intentionally excluded here. User items are hard-deleted via two paths:
        // 1. AccountDeleter proactively removes user items during account deletion
        // 2. RoomHardDeleter cascades user deletion when a room is finally removed
        //    (via the legacy cs_user_manager::deleteFromDb() call inside RoomHardDeletionHelper)
        // Activating CS_USER_TYPE here would hit orphaned user records without proper FK cleanup.
        // $itemTypes[] = CS_USER_TYPE;

        $deleteDays = $this->parameterBag->get('commsy.settings.delete_days');
        if (empty($deleteDays) || !is_numeric($deleteDays)) {
            return;
        }
        $deleteDays = (int) $deleteDays;

        // Rubric items first — deletes orphan rubric rows that outlived
        // their room without going through the room hard-delete path.
        foreach ($itemTypes as $itemType) {
            $manager = $this->legacyEnvironment->getManager($itemType);
            $manager->deleteReallyOlderThan($deleteDays);
        }

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
