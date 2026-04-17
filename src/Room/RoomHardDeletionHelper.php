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

namespace App\Room;

use App\Hash\HashManager;
use App\Services\LegacyEnvironment;
use App\Utils\ReaderService;
use cs_environment;

/**
 * Encapsulates the per-room *hard*-delete cascade — the physical `DELETE`
 * wave that runs after the configured soft-delete grace period elapses.
 *
 * This helper centralises what used to live inline in
 * {@see \cs_room_manager::deleteReallyOlderThan()}: one room-directory
 * wipe, one reader purge, one hash purge, and 20 per-table
 * `DELETE WHERE context_id = :roomId` calls routed through the matching
 * legacy manager's `deleteFromDb()` method.
 *
 * Scope for commit 19: this remains a thin wrapper over the existing
 * legacy `deleteFromDb()` chain. The per-manager implementations vary in
 * complexity (most do straight `DELETE WHERE context_id`; some — e.g.
 * {@see \cs_user_manager::deleteFromDb()} — null out FK references before
 * the delete, and {@see \cs_link_modifier_item_manager::deleteFromDb()}
 * scopes via `modifier_id IN (user_ids)` instead of `context_id`). The
 * plan (see `/Users/cschoenf/.claude/plans/temporal-baking-pillow.md`,
 * "Fallstricke") explicitly calls out reviewing each of the 20 steps
 * individually before porting to raw DBAL — so the naive "replace with
 * bulk `DELETE FROM <table> WHERE context_id`" is *not* a safe drop-in
 * and is deferred.
 *
 * Invoked exclusively by {@see RoomHardDeleter} and the per-type
 * `RoomDeleter::hardDeleteRoom()` adapters.
 */
readonly class RoomHardDeletionHelper
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private HashManager $hashManager,
        private ReaderService $readerService,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Physically removes everything belonging to `$roomId` across the
     * 20 per-rubric tables, plus the room's files directory on disk,
     * all reader rows, and all password-hash rows for users that lived
     * in the room.
     *
     * Callers MUST have ensured the room (and its rubric content) is
     * already soft-deleted — this helper does not dispatch lifecycle
     * events. `$contextId` is the legacy `contextId` of the room item
     * (= the portal id for top-level rooms, or the parent room id for
     * userrooms / grouprooms); it is only used by
     * {@see \cs_disc_manager::removeRoomDir()} to locate the on-disk
     * `files/<portalId>/<roomId>/` directory.
     */
    public function purgeRoomData(int $contextId, int $roomId): void
    {
        // 1. File-system directory — removed first so that even if a
        //    subsequent step fails we do not leave orphaned files behind
        //    on disk. `removeRoomDir()` is itself a best-effort rm-rf and
        //    tolerates a missing directory.
        $discManager = $this->legacyEnvironment->getDiscManager();
        $discManager->removeRoomDir($contextId, $roomId);

        // 2. Reader rows (per-user "seen" markers for every item in the
        //    room) — bulk-deleted via `App\Entity\Reader` DQL.
        $this->readerService->deleteAllEntriesInWorkspace($roomId);

        // 3. Password hashes for users that lived in the room. Runs
        //    before the user table delete because it looks users up via
        //    the room's user_manager.
        $this->hashManager->deleteHashesInContext($roomId);

        // 4. Per-table bulk deletes — routed through the legacy manager
        //    methods to preserve their individual quirks (FK null-outs
        //    in cs_user_manager, modifier_id scoping in
        //    cs_link_modifier_item_manager, file-id resolution in
        //    cs_link_item_file_manager). Order mirrors legacy's
        //    `cs_room_manager::deleteReallyOlderThan()`.
        $env = $this->legacyEnvironment;
        $env->getLinkModifierItemManager()->deleteFromDb($roomId);
        $env->getLinkItemFileManager()->deleteFromDb($roomId);
        $env->getAnnotationManager()->deleteFromDb($roomId);
        $env->getAnnouncementManager()->deleteFromDb($roomId);
        $env->getDatesManager()->deleteFromDb($roomId);
        $env->getDiscussionArticlesManager()->deleteFromDb($roomId);
        $env->getDiscussionManager()->deleteFromDb($roomId);
        $env->getFileManager()->deleteFromDb($roomId);
        $env->getItemManager()->deleteFromDb($roomId);
        $env->getLabelManager()->deleteFromDb($roomId);
        $env->getLinkManager()->deleteFromDb($roomId);
        $env->getLinkItemManager()->deleteFromDb($roomId);
        $env->getMaterialManager()->deleteFromDb($roomId);
        $env->getSectionManager()->deleteFromDb($roomId);
        $env->getStepManager()->deleteFromDb($roomId);
        $env->getTagManager()->deleteFromDb($roomId);
        $env->getTag2TagManager()->deleteFromDb($roomId);
        $env->getTaskManager()->deleteFromDb($roomId);
        $env->getTodosManager()->deleteFromDb($roomId);
        $env->getUserManager()->deleteFromDb($roomId);

        // 5. Finally the `room` row itself — ORM remove so the
        //    `room_slug` orphan-removal cascade fires (legacy parity).
        $env->getRoomManager()->deleteFromDb($roomId);
    }
}
