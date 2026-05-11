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

namespace App\Hash;

use App\Entity\Hash;
use App\Entity\Room;
use App\Repository\HashRepository;
use App\Repository\RoomRepository;
use App\Room\RoomAccessChecker;
use App\Services\LegacyEnvironment;
use cs_context_item;
use cs_environment;
use cs_user_item;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class HashManager
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private HashRepository $hashRepository,
        LegacyEnvironment $legacyEnvironment,
        private readonly RoomAccessChecker $roomAccessChecker,
        private readonly RoomRepository $roomRepository,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Routes the legacy `cs_context_item` to its Doctrine `Room` twin
     * and asks {@see RoomAccessChecker::canEnterByUserItemId()} — the
     * identifier-only mirror of `mayEnterByUserItemID()`. Hash logins
     * (RSS / iCal) hand around the room-scoped user_item id, which is
     * exactly what `canEnterByUserItemId` consumes.
     */
    private function hashUserCanEnter(cs_context_item $context, int $userItemId): bool
    {
        $room = $this->roomRepository->find($context->getItemID());
        return $room instanceof Room && $this->roomAccessChecker->canEnterByUserItemId($userItemId, $room);
    }

    public function getUserHashes(int $userId): Hash
    {
        $hash = $this->hashRepository->findByUserId($userId);

        return $hash ?? $this->hashRepository->createHash($userId);
    }

    public function isRssHashValid(string $hash, cs_context_item $context): bool
    {
        try {
            $hash = $this->hashRepository->findByRssHash($hash);
            $canEnter = $this->hashUserCanEnter($context, (int) $hash->getUserId());
            if ($canEnter) {
                return true;
            }

            $this->hashRepository->deleteHash($hash);
            return false;
        } catch (NoResultException|NonUniqueResultException $e) {
            return false;
        }
    }

    public function isICalHashValid(string $hash, cs_context_item $context): bool
    {
        try {
            $hash = $this->hashRepository->findByICalHash($hash);
            $canEnter = $this->hashUserCanEnter($context, (int) $hash->getUserId());
            if ($canEnter) {
                return true;
            }

            $this->hashRepository->deleteHash($hash);
            return false;
        } catch (NoResultException|NonUniqueResultException $e) {
            return false;
        }
    }

    public function deleteHashesInContext(int $contextId): void
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($contextId);
        $userManager->select();
        $userList = $userManager->get();

        $userIds = $userList->getIDArray();
        $this->hashRepository->deleteHashesByUserIds($userIds);
    }
}
