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
use App\Security\Permission\Legacy\LegacyPermissionBridge;
use App\Services\LegacyEnvironment;
use cs_context_item;
use cs_environment;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class HashManager
{
    /**
     * Time-to-live of an account-merge token. Deliberately short-lived: the token
     * legitimises a destructive merge, so it must not stay valid for long.
     */
    public const MERGE_TOKEN_TTL = 'PT1H';

    private cs_environment $legacyEnvironment;

    public function __construct(
        private HashRepository $hashRepository,
        LegacyEnvironment $legacyEnvironment,
        private readonly LegacyPermissionBridge $legacyBridge,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getUserHashes(int $userId): Hash
    {
        $hash = $this->hashRepository->findByUserId($userId);

        return $hash ?? $this->hashRepository->createHash($userId);
    }

    /**
     * Creates a single-use, short-lived merge token bound to the initiating user's
     * hash row. Stores both account ids so the confirmation step does not depend on
     * an active session. Returns the generated token (to be mailed to account A).
     *
     * @param int $userId        portal-user id of the initiator (surviving account N)
     * @param int $fromAccountId account A that will be merged in and deleted
     * @param int $intoAccountId surviving account N
     */
    public function createMergeHash(int $userId, int $fromAccountId, int $intoAccountId): string
    {
        $token = bin2hex(random_bytes(32));

        $hash = $this->getUserHashes($userId);
        $hash->setMergeToken($token);
        $hash->setMergeFromAccountId($fromAccountId);
        $hash->setMergeIntoAccountId($intoAccountId);
        $hash->setMergeExpiresAt((new \DateTimeImmutable())->add(new \DateInterval(self::MERGE_TOKEN_TTL)));

        $this->hashRepository->save($hash);

        return $token;
    }

    /**
     * Looks up a pending merge by token and verifies it has not expired. Expired
     * tokens are cleared on access. Returns null when no valid token matches.
     */
    public function findValidMergeHash(string $token): ?Hash
    {
        if ('' === $token) {
            return null;
        }

        try {
            $hash = $this->hashRepository->findByMergeToken($token);
        } catch (NonUniqueResultException) {
            return null;
        }

        if (null === $hash || null === $hash->getMergeExpiresAt()) {
            return null;
        }

        if ($hash->getMergeExpiresAt() < new \DateTimeImmutable()) {
            $this->consumeMergeHash($hash);

            return null;
        }

        return $hash;
    }

    /**
     * Clears the merge token (single-use consumption); feed hashes stay intact.
     */
    public function consumeMergeHash(Hash $hash): void
    {
        $hash->clearMerge();
        $this->hashRepository->save($hash);
    }

    public function isRssHashValid(string $hash, cs_context_item $context): bool
    {
        try {
            $hash = $this->hashRepository->findByRssHash($hash);
            $canEnter = $this->legacyBridge->userItemIdCanEnter($context, (int) $hash->getUserId());
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
            $canEnter = $this->legacyBridge->userItemIdCanEnter($context, (int) $hash->getUserId());
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
