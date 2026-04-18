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
use App\Rubric\RubricHardDeleter;
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
        private RubricHardDeleter $rubricHardDeleter,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        // Auxiliary tables that do not have a registered RubricDeleter yet
        // — shared `items` twin rows, `links` / `link_items`, tag pivots,
        // `task` (room-scope, not a rubric), plus the two file tables with
        // non-standard physical-cleanup needs (`files` has filesystem
        // side effects, `item_link_file` is a pure join table). These
        // still go through `cs_*_manager::deleteReallyOlderThan()` for
        // now; future commits will migrate them as dedicated hard-delete
        // services (FileHardDeleter, etc.) land.
        //
        // Rubric-primary types (Announcement, Annotation, Date,
        // Discussion, Label, Material, Todo) have moved to the
        // RubricHardDeleter path below — it iterates the registered
        // `app.rubric.deleter` services so each rubric owns its own
        // physical-cleanup SQL, including sub-entry tables
        // (`section` / `step` / `discussionarticles`).
        //
        // CS_ROOM_TYPE sits on its own RoomHardDeleter path further down.
        $legacyItemTypes = [];
        $legacyItemTypes[] = CS_LINKITEMFILE_TYPE;
        $legacyItemTypes[] = CS_FILE_TYPE;
        $legacyItemTypes[] = CS_ITEM_TYPE;
        $legacyItemTypes[] = CS_LINK_TYPE;
        $legacyItemTypes[] = CS_LINKITEM_TYPE;
        $legacyItemTypes[] = CS_TAG_TYPE;
        $legacyItemTypes[] = CS_TAG2TAG_TYPE;
        $legacyItemTypes[] = CS_TASK_TYPE;

        // CS_USER_TYPE is intentionally excluded here. User items are hard-deleted via two paths:
        // 1. AccountDeleter proactively removes user items during account deletion
        // 2. RoomHardDeleter cascades user deletion when a room is finally removed
        //    (via the legacy cs_user_manager::deleteFromDb() call inside RoomHardDeletionHelper)
        // Activating CS_USER_TYPE here would hit orphaned user records without proper FK cleanup.
        // $legacyItemTypes[] = CS_USER_TYPE;

        $deleteDays = $this->parameterBag->get('commsy.settings.delete_days');
        if (empty($deleteDays) || !is_numeric($deleteDays)) {
            return;
        }
        $deleteDays = (int) $deleteDays;

        // Rubric primary types: each registered RubricDeleter sweeps its
        // own tables (including owned sub-entries).
        $this->rubricHardDeleter->hardDeleteOlderThan($deleteDays);

        // Auxiliary / not-yet-migrated tables — legacy manager sweep.
        foreach ($legacyItemTypes as $itemType) {
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
