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

namespace Tests\Integration\Security\Voter\Concerns;

use App\Entity\Account;
use App\Entity\Lock;
use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_user_item;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionObject;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;

/**
 * Shared scaffolding for ItemVoter / FileVoter / RubricVoter / etc.
 * characterization tests.
 *
 * Each test class:
 *   - calls {@see bootVoterContext()} from setUp()
 *   - logs the actor in via {@see loginAs()} — passing a Room sets the
 *     legacy currentUserItem to the room-level cs_user_item, omitting it
 *     uses the portal-level user_item. Mirrors LegacySubscriber's
 *     per-request setup.
 *
 * The trait also bundles the room/member/lock/cache-eviction helpers that
 * almost every voter test needs. Test-specific helpers (e.g. portal-status
 * mutation, extras manipulation, factory states for unusual room configs)
 * stay in the test file that uses them.
 */
trait BootsVoter
{
    protected cs_environment $legacyEnvironment;
    protected AuthorizationCheckerInterface $authChecker;
    protected UserService $userService;
    protected Account $portalAccount;

    // ----------------------------------------------------- boot / accounts

    protected function bootVoterContext(Account $portalAccount): void
    {
        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->authChecker = self::getContainer()
            ->get('security.authorization_checker');
        $this->userService = self::getContainer()->get(UserService::class);
        $this->portalAccount = $portalAccount;
    }

    /**
     * Creates a fresh Account, by default in the same portal as the seed
     * account. Pass a {@param $portal} to attach the account to a different
     * portal (e.g. one created via PortalFactory::new()->locked() for
     * Portal-state characterization). Always wires authSource explicitly
     * because {@see AccountFactory} only auto-resolves it when no portal
     * is passed.
     */
    protected function createPortalAccount(?string $username = null, ?Portal $portal = null): Account
    {
        $portal ??= $this->portalAccount->getPortal();
        $attrs = [
            'portal' => $portal,
            'authSource' => $portal?->getAuthSources()->first(),
        ];
        if ($username !== null) {
            $attrs['username'] = $username;
        }
        return AccountFactory::createOne($attrs);
    }

    /**
     * Item id of an account's portal-level cs_user_item — the row the portal
     * settings account index lists and its actions address.
     */
    protected function portalUserItemId(Account $account): int
    {
        $userItem = $this->userService->getUserInContext(
            $account,
            $account->getPortal()?->getId() ?? 0
        );
        if (!$userItem instanceof cs_user_item) {
            self::fail(sprintf(
                'No portal-level cs_user_item for account "%s"',
                $account->getUsername(),
            ));
        }

        return $userItem->getItemID();
    }

    // ----------------------------------------------------- login / token

    /**
     * Sets the Symfony token AND primes the legacy currentUserItem.
     *
     * If a $room is given, currentUserItem is the room-level cs_user_item
     * for the account in that room (LegacySubscriber's per-room setup).
     * Otherwise it's the portal-level cs_user_item — used for ITEM_ENTER
     * tests where the actor may not yet be a member of the target room.
     *
     * For the Account.username='root' short-circuit there is nothing to
     * prime; use {@see actAsRoot()} which only sets the token.
     */
    protected function loginAs(Account $account, ?Room $room = null): void
    {
        $this->setSecurityToken($account);

        $contextId = $room !== null
            ? $room->getItemId()
            : ($account->getPortal()?->getId() ?? 0);

        $userItem = $this->userService->getUserInContext($account, $contextId);
        if (!$userItem instanceof cs_user_item) {
            self::fail(sprintf(
                'Account "%s" has no cs_user_item in context %d (was the membership created?)',
                $account->getUsername(),
                $contextId,
            ));
        }

        $this->legacyEnvironment->setCurrentPortalID($account->getPortal()?->getId());
        $this->legacyEnvironment->setCurrentContextID($contextId);
        $this->legacyEnvironment->setCurrentUserItem($userItem);
    }

    /**
     * Token-only login for the Account.username='root' short-circuit. The
     * voter returns true at the top before reading currentUserItem, so no
     * legacy priming is needed.
     */
    protected function actAsRoot(Account $rootAccount): void
    {
        $this->setSecurityToken($rootAccount);
    }

    protected function setSecurityToken(Account $account): void
    {
        $token = new UsernamePasswordToken($account, 'main', $account->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    // ----------------------------------------------------- room helpers

    /**
     * Default factory args (contextId + portal) shared by every room created
     * in voter tests. Tests that need exotic states (archived, locked,
     * lockedByModerator, openForGuests, …) chain them on the factory:
     *
     *   RoomFactory::new()->project()->archived()->create($this->roomDefaults());
     */
    protected function roomDefaults(): array
    {
        return [
            'contextId' => $this->portalAccount->getPortal()?->getId(),
            'portal' => $this->portalAccount->getPortal(),
        ];
    }

    protected function createProjectRoom(): Room
    {
        return RoomFactory::new()->project()->create($this->roomDefaults());
    }

    protected function createCommunityRoom(): Room
    {
        return RoomFactory::new()->community()->create($this->roomDefaults());
    }

    protected function createPrivateRoom(): Room
    {
        return RoomFactory::new()->privateRoom()->create($this->roomDefaults());
    }

    protected function createUserRoom(): Room
    {
        return RoomFactory::new()->userRoom()->create($this->roomDefaults());
    }

    protected function createGroupRoom(): Room
    {
        return RoomFactory::new()->groupRoom()->create($this->roomDefaults());
    }

    protected function createMember(Account $account, Room $room, int $status): User
    {
        return RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
            'status' => $status,
        ]);
    }

    // ----------------------------------------------------- mutators

    /**
     * Promotes the given account's portal-level cs_user_item to status=3.
     * Required to test paths that branch on `userIsPortalModerator()`.
     *
     * Uses the legacy `makeModerator()` + `save()` pipeline (consistent with
     * {@see setImpersonationGrant()}) and evicts caches afterwards. The
     * eviction is defensive: today's tests call this method before
     * `loginAs()` so the user_manager cache is empty, but a future test
     * that loads the user first would otherwise see a stale cs_user_item.
     */
    protected function promoteToPortalModerator(Account $account): void
    {
        $portalId = $account->getPortal()?->getId() ?? 0;
        $userItem = $this->userService->getUserInContext($account, $portalId);
        if (!$userItem instanceof cs_user_item) {
            self::fail(sprintf(
                'No portal-level cs_user_item for account "%s"',
                $account->getUsername(),
            ));
        }

        $userItem->makeModerator();
        $userItem->save();
        $this->evictLegacyCache($userItem->getItemID());
    }

    /**
     * Soft-deletes a room via raw DBAL. Pair with {@see evictLegacyCache()}
     * if the test re-asks the legacy item/room manager for that id —
     * MaterialDeleter / RoomDeleter / direct UPDATEs do NOT invalidate the
     * legacy `_cache_object` / `_cached_items` arrays.
     */
    protected function softDeleteRoom(int $roomItemId): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $conn = $this->dbConnection();
        $conn->update('items', ['deletion_date' => $now], ['item_id' => $roomItemId]);
        $conn->update('room', ['deletion_date' => $now], ['item_id' => $roomItemId]);
    }

    /**
     * Persists an active Lock entity for {@param $itemId} owned by {@param $owner}.
     * Used to characterize the canEditLock branch where another user holds
     * an active lock and the voter rejects EDIT.
     */
    protected function placeLock(int $itemId, Account $owner): void
    {
        $em = $this->em();
        $lock = new Lock();
        $lock->setItemId($itemId);
        $lock->setAccount($em->getRepository(Account::class)->find($owner->getId()));
        $lock->setToken(bin2hex(random_bytes(16)));
        $em->persist($lock);
        $em->flush();
    }

    /**
     * Drops cached cs_*_item / row entries for {@param $itemId} from every
     * legacy manager that might hold them. Required after a raw DBAL UPDATE
     * (or after a Deleter that does raw UPDATEs) so the next getItem() call
     * actually re-reads the row.
     *
     * The legacy managers keep TWO independent caches: `_cache_object`
     * (built items) and `_cached_items` / `rowCache` (raw rows). Both must
     * be cleared.
     *
     * Prefer setting state at factory creation time (which goes through
     * legacy `save()` and updates the cache correctly). Use this only when
     * characterizing a state that is reachable only via raw DBAL UPDATE —
     * primarily post-Deleter scenarios.
     */
    protected function evictLegacyCache(int $itemId): void
    {
        $env = $this->legacyEnvironment;
        $managers = [
            $env->getItemManager(),
            $env->getRoomManager(),
            $env->getProjectManager(),
            $env->getCommunityManager(),
            $env->getMaterialManager(),
            $env->getUserManager(),
            $env->getDatesManager(),
        ];

        foreach ($managers as $manager) {
            $rfl = new ReflectionObject($manager);
            foreach (['_cache_object', 'rowCache', '_cached_items'] as $name) {
                if (!$rfl->hasProperty($name)) {
                    continue;
                }
                $prop = $rfl->getProperty($name);
                $prop->setAccessible(true);
                $cache = $prop->getValue($manager);
                if (is_array($cache) && array_key_exists($itemId, $cache)) {
                    unset($cache[$itemId]);
                    $prop->setValue($manager, $cache);
                }
            }
            // The SQL-result cache is keyed by the full query string, not
            // by item id, so we can't selectively evict — drop all entries.
            // This is safe because the manager will simply re-query on the
            // next select().
            foreach (['_cache_sql', '_cached_sql'] as $name) {
                if (!$rfl->hasProperty($name)) {
                    continue;
                }
                $prop = $rfl->getProperty($name);
                $prop->setAccessible(true);
                $prop->setValue($manager, []);
            }
        }
    }

    /**
     * Sets the impersonation grant on the portal-level cs_user_item via the
     * legacy API. The legacy `save()` runs a raw DBAL UPDATE without
     * refreshing the manager's `_cache_object` / `_cached_items`, so we
     * also evict explicitly — otherwise a subsequent `select()` rebuilds
     * the cs_user_item from the now-stale row cache.
     */
    protected function setImpersonationGrant(
        Account $account,
        bool $allowed,
        ?\DateTimeImmutable $expiry = null,
    ): void {
        $portalId = $account->getPortal()?->getId() ?? 0;
        $userItem = $this->userService->getUserInContext($account, $portalId);
        if (!$userItem instanceof cs_user_item) {
            self::fail(sprintf(
                'No portal-level cs_user_item for account "%s"',
                $account->getUsername(),
            ));
        }

        $userItem->setCanImpersonateAnotherUser($allowed);
        $userItem->setImpersonateExpiryDate($expiry);
        $userItem->save();
        $this->evictLegacyCache($userItem->getItemID());
    }

    // ----------------------------------------------------- accessors

    protected function dbConnection(): Connection
    {
        return $this->em()->getConnection();
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
