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
use App\Repository\HashRepository;
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
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getUserHashes(int $userId): Hash
    {
        try {
            return $this->hashRepository->findByUserId($userId);
        } catch (NoResultException $e) {
            $this->hashRepository->createHash($userId);
            return $this->hashRepository->findByUserId($userId);
        }
    }

    public function isRssHashValid(string $hash, cs_context_item $context): bool
    {
        try {
            $hash = $this->hashRepository->findByRssHash($hash);
            $canEnter = $context->mayEnterByUserItemID($hash->getUserId());
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
            $canEnter = $context->mayEnterByUserItemID($hash->getUserId());
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
