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
 * Per-room hard-delete cascade: FS wipe, reader/hash purge, and the 20
 * per-table deletes routed through legacy `deleteFromDb()`. Replaces the
 * inline body of `cs_room_manager::deleteReallyOlderThan()`.
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
     * Physically removes everything belonging to `$roomId`: files directory,
     * reader rows, password hashes, the 20 per-table deletes, and finally
     * the `room` row itself. Callers MUST ensure content is already soft-
     * deleted; this helper dispatches no events.
     *
     * `$contextId` is only used to locate the on-disk files directory.
     */
    public function purgeRoomData(int $contextId, int $roomId): void
    {
        // FS first so a later failure doesn't leave orphaned files.
        $discManager = $this->legacyEnvironment->getDiscManager();
        $discManager->removeRoomDir($contextId, $roomId);

        $this->readerService->deleteAllEntriesInWorkspace($roomId);

        // Hashes before users — the lookup goes via the room's user_manager.
        $this->hashManager->deleteHashesInContext($roomId);

        // Per-table deletes via legacy managers to preserve per-manager quirks
        // (FK null-outs, modifier_id scoping, file-id resolution). Order mirrors
        // legacy `cs_room_manager::deleteReallyOlderThan()`.
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

        // ORM remove so the `room_slug` orphan-removal cascade fires.
        $env->getRoomManager()->deleteFromDb($roomId);
    }
}
