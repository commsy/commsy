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
 * Bootstraps the legacy `cs_environment` so `getNewItem()->save()` chains work
 * in tests. Legacy saves require request-scoped state (current portal id,
 * context id, `cs_user_item`) which production pulls from the HTTP request —
 * this trait translates a Foundry-created Room + User into the legacy slots.
 * Without priming, factory-created items are invisible to `getItem()`-based
 * code under test.
 */
trait PrimesLegacyEnvironment
{
    /**
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

    abstract protected function getLegacyEnvironmentService(): LegacyEnvironment;

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
