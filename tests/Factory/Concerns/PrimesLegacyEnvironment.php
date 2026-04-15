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

namespace Tests\Factory\Concerns;

use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use LogicException;

/**
 * Bootstraps the legacy `cs_environment` so that a legacy manager's
 * `getNewItem()->save()` chain can run correctly from within tests.
 *
 * The legacy save logic (see `cs_manager::saveItem()`) depends on three
 * pieces of request-scoped state being set:
 *
 *  - the current portal id (scopes permissions and queries)
 *  - the current context id (becomes `context_id` in the rubric tables)
 *  - the current `cs_user_item` (becomes `creator_id`/`modifier_id`)
 *
 * Production code picks these up from the HTTP request / security token.
 * In tests we have a persisted {@see Room} plus {@see User} from a Foundry
 * story, and translate them into the legacy world here — once, in one place,
 * so individual factories stay focused on their own data.
 */
trait PrimesLegacyEnvironment
{
    /**
     * Prepares the legacy environment so that subsequent manager calls
     * (`$env->getAnnouncementManager()->getNewItem()->save()` and friends)
     * pick up the expected creator, modifier and context.
     *
     * Idempotent: safe to call multiple times in the same test.
     */
    protected function primeLegacyEnvironment(Room $room, User $actor): cs_environment
    {
        $legacyService = $this->getLegacyEnvironmentService();
        $env = $legacyService->getEnvironment();

        $portal = $actor->getPortal();
        if ($portal === null) {
            throw new LogicException(sprintf(
                'Cannot prime legacy environment: %s has no portal.',
                User::class
            ));
        }

        $env->setCurrentPortalID($portal->getId());
        $env->setCurrentContextID($room->getItemId());
        $env->setCurrentUserItem($this->resolveLegacyUserItem($env, $room, $actor));

        return $env;
    }

    /**
     * Returns the LegacyEnvironment service. Individual factories inject the
     * service themselves; this method provides the single access point the
     * trait relies on.
     */
    abstract protected function getLegacyEnvironmentService(): LegacyEnvironment;

    /**
     * Translates a Doctrine {@see User} into its legacy `cs_user_item`
     * counterpart by querying the legacy user manager for the user's item id
     * inside the given room context.
     */
    private function resolveLegacyUserItem(cs_environment $env, Room $room, User $actor): cs_user_item
    {
        $itemId = $actor->getItemId();
        if (!$itemId) {
            throw new LogicException(sprintf(
                'Cannot prime legacy environment: %s must be persisted (item_id missing).',
                User::class
            ));
        }

        $userManager = $env->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextLimit($room->getItemId());
        $userManager->setUserIDLimit($actor->getUserId());
        $userManager->select();

        /** @var cs_user_item|null $userItem */
        $userItem = $userManager->get()?->getFirst();
        if (!$userItem instanceof cs_user_item) {
            throw new LogicException(sprintf(
                'Cannot prime legacy environment: no legacy user item for user %d in context %d.',
                $itemId,
                $room->getItemId()
            ));
        }

        return $userItem;
    }
}
