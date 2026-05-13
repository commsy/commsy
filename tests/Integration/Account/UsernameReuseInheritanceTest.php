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

namespace Tests\Integration\Account;

use App\Account\AccountDeleter;
use App\Account\AccountManager;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Facade\AccountCreatorFacade;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

use function Zenstruck\Foundry\Persistence\repository;

/**
 * Phase 0 — reproduction test for the username-reuse inheritance bug.
 *
 * Scenario: an account is deprovisioned, but a "stale" non-soft-deleted user row
 * survives somewhere (orphan: `account_id IS NULL`, `deletion_date IS NULL`).
 * A new account is later registered with the same `(username, auth_source)` in
 * the same portal. The new account then "inherits" the orphan room membership,
 * because all legacy lookups still join on `(user_id, auth_source)` rather than
 * on `account_id`.
 *
 * Both tests are skipped on the current `feature/user-consistency-refactor`
 * baseline and will be unskipped as the corresponding phases land:
 *
 *  - {@see testAccountDeleteSweepsOrphanRow} — unskip in Phase 1
 *    (AccountDeleter orphan sweep)
 *  - {@see testReRegisteredAccountDoesNotInheritOrphanRoomMembership} — unskip
 *    in Phase 3 (identity key migrated to `account_id`)
 *
 * Both tests would be RED today if the `markTestSkipped` lines were removed —
 * that is the definition of done for Phase 0.
 */
final class UsernameReuseInheritanceTest extends KernelTestCase
{
    /**
     * Assertion 1 — after AccountDeleter::delete(), no non-soft-deleted user row
     * may exist for the deleted account's `(username, auth_source)` in the
     * portal, regardless of whether the row had a valid `account_id` FK.
     *
     * Today this fails because `AccountDeleter` collects user rows via
     * `UserListBuilder->fromAccount(...)`, which derives its context list from
     * the official portal-user membership graph. Orphan rows with
     * `account_id IS NULL` outside that graph are missed.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAccountDeleteSweepsOrphanRow(): void
    {
        self::bootKernel();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');

        // Simulate the historical inconsistency: a room-user row that lives in
        // the user table but has lost its account_id link. This is the very
        // shape of `Klasse A` orphans the bug is built on.
        $this->getConnection()->executeStatement(
            'UPDATE user SET account_id = NULL WHERE item_id = :id',
            ['id' => $roomUser->getItemId()]
        );

        $username = $account->getUsername();
        $authSourceId = $account->getAuthSource()->getId();

        // Synchronous delete — we want to inspect the DB state immediately,
        // not via the Messenger transport (parity with AccountLifecycleTest).
        $this->getAccountDeleter()->delete($account);

        $survivors = $this->getConnection()->fetchAllAssociative(
            'SELECT item_id, context_id, account_id
               FROM user
              WHERE user_id     = :username
                AND auth_source = :authSource
                AND deletion_date IS NULL
                AND deleter_id  IS NULL',
            ['username' => $username, 'authSource' => $authSourceId]
        );

        self::assertSame(
            [],
            $survivors,
            sprintf(
                'AccountDeleter left %d non-soft-deleted user row(s) behind for username "%s" in portal %d. '
                . 'The orphan in room %d (item %d) is the proof of the bug — Phase 1 must sweep it.',
                count($survivors),
                $username,
                $account->getPortal()->getId(),
                $room->getItemId(),
                $roomUser->getItemId(),
            )
        );
    }

    /**
     * Assertion 2 — after a new account is registered with the same
     * `(username, auth_source)` and the standard post-login profile sync runs,
     * the new account must not "inherit" room memberships that belonged to the
     * deleted predecessor.
     *
     * Defense-in-depth scenario: Phase 1's sweep would normally clean up the
     * orphan when A1 is deleted, and the Phase 1 Facade guard would block A2
     * being created on top of a remaining orphan. To isolate the Phase 3
     * change — `propagateAccountDataToProfiles` and friends now key on
     * account_id instead of (user_id, auth_source) — we re-inject the orphan
     * AFTER A2 already exists, then verify that the legacy lookup path no
     * longer rewrites it onto the new account.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testReRegisteredAccountDoesNotInheritOrphanRoomMembership(): void
    {
        self::bootKernel();

        /** @var Account $a1 */
        $a1 = RoomWithMemberStory::get('account');
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $a1RoomUser */
        $a1RoomUser = RoomWithMemberStory::get('roomUser');

        $username = $a1->getUsername();
        $portal = $a1->getPortal();
        $authSource = $a1->getAuthSource();
        $roomUserItemId = $a1RoomUser->getItemId();

        // Delete the old account. Phase 1's sweep soft-deletes every related
        // user row including the project-room one we care about, so A2 can be
        // registered cleanly afterwards without tripping the Facade guard.
        $this->getAccountDeleter()->delete($a1);

        // Register a new account with the very same username + auth_source.
        $a2 = AccountFactory::createOne([
            'username' => $username,
            'portal' => $portal,
            'authSource' => $authSource,
            'firstname' => 'NewFirst',
            'lastname' => 'NewLast',
            'email' => 'new@example.test',
        ]);

        // Now inject the historical orphan shape onto the previous project-room
        // row: revive it as a non-soft-deleted row with account_id = NULL.
        // Phase 3 must make the lookup ignore this row regardless of whether
        // its (user_id, auth_source) matches the new account.
        $connection = $this->getConnection();
        $connection->executeStatement(
            'UPDATE user
                SET deletion_date = NULL, deleter_id = NULL, account_id = NULL
              WHERE item_id = :id',
            ['id' => $roomUserItemId]
        );

        // Simulate the post-login profile propagation for A2.
        $this->getAccountManager()->propagateAccountDataToProfiles($a2);

        // Two assertions on the orphan row in $room:
        //
        // (a) account_id stays NULL — Phase 3 must not silently re-attach the
        //     orphan to the new account.
        // (b) firstname / email are not overwritten with the new account's
        //     data — that overwrite is the bug we are guarding against.
        $row = $connection->fetchAssociative(
            'SELECT account_id, firstname, lastname, email
               FROM user
              WHERE item_id = :id',
            ['id' => $a1RoomUser->getItemId()]
        );

        self::assertIsArray($row);
        self::assertNull(
            $row['account_id'] === null ? null : (int) $row['account_id'],
            'Orphan row was reattached to the new account — Phase 3 lookups should never bind orphans to a fresh account.'
        );
        self::assertNotSame(
            'NewFirst',
            $row['firstname'],
            'Orphan row firstname was overwritten by propagateAccountDataToProfiles — '
            . 'this is the username-inheritance bug.'
        );
        self::assertNotSame(
            'new@example.test',
            $row['email'],
            'Orphan row email was overwritten by propagateAccountDataToProfiles — '
            . 'this is the username-inheritance bug.'
        );

        // Additionally, no non-soft-deleted user row in $room must belong to the
        // new account (it never joined that room).
        /** @var UserRepository $userRepository */
        $userRepository = repository(User::class);
        $newAccountInRoom = $userRepository->count([
            'account' => $a2,
            'room' => $room,
            'deletionDate' => null,
            'deleter' => null,
        ]);
        self::assertSame(
            0,
            $newAccountInRoom,
            sprintf(
                'New account %d ended up as a member of room %d without ever joining it — '
                . 'orphan room membership was inherited.',
                $a2->getId(),
                $room->getItemId(),
            )
        );
    }

    private function getAccountDeleter(): AccountDeleter
    {
        return self::getContainer()->get(AccountDeleter::class);
    }

    private function getAccountManager(): AccountManager
    {
        return self::getContainer()->get(AccountManager::class);
    }

    private function getConnection(): Connection
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    /**
     * @see AccountCreatorFacade — referenced here only to anchor the test on
     * the actual signup pathway used in production.
     */
    private function getAccountCreatorFacade(): AccountCreatorFacade
    {
        return self::getContainer()->get(AccountCreatorFacade::class);
    }
}
