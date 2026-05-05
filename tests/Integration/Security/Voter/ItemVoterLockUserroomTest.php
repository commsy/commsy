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

namespace Tests\Integration\Security\Voter;

use App\Security\Authorization\Voter\ItemVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for the lock-related ItemVoter attributes
 * (ITEM_EDIT_LOCK, ITEM_FILE_LOCK) and the userroom-privilege attribute
 * (ITEM_USERROOM).
 *
 * ITEM_EDIT_LOCK: voter returns true when the user is root, when the item
 * type does not support locking, or when the user can take/refresh the
 * lock. Returns false only when supportsLocking() AND another user holds
 * a non-expired lock.
 *
 * ITEM_FILE_LOCK: voter returns true when WOPI discovery is unavailable
 * (default test setup) — the WOPI-aware "file is locked by Collabora"
 * branch is exercised only when WOPIDiscovery is wired up.
 *
 * ITEM_USERROOM: voter returns true ONLY when the item lives in a userroom
 * context AND the actor can participate (status >= 2 AND room not archived).
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class ItemVoterLockUserroomTest extends KernelTestCase
{
    use BootsVoter;

    // ---------- ITEM_EDIT_LOCK ----------

    public function testEditLockReturnsTrueWhenNoLockExists(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT_LOCK, $material->getItemId()),
            'When no lock exists, EDIT_LOCK is granted (LockManager::userCanLock returns true)',
        );
    }

    public function testEditLockReturnsFalseWhenAnotherUserHoldsLock(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        $lockHolder = $this->createPortalAccount();
        $this->createMember($lockHolder, $room, 2);
        $this->placeLock($material->getItemId(), $lockHolder);

        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::EDIT_LOCK, $material->getItemId()),
            'When another user holds the lock, EDIT_LOCK is denied',
        );
    }

    public function testEditLockReturnsTrueWhenSameUserAlreadyHoldsLock(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        $this->placeLock($material->getItemId(), $this->portalAccount);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT_LOCK, $material->getItemId()),
            'A user already holding a lock can keep editing (lock.account === current user)',
        );
    }

    public function testEditLockReturnsTrueForUnlockableItemType(): void
    {
        // User items don't support locking (LockManager::supportsLocking
        // restricts to MATERIAL/ANNOUNCEMENT/DATE/DISCUSSION/GROUP/TODO/
        // TOPIC/SECTION/STEP). For everything else, EDIT_LOCK = true.
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);

        $this->loginAs($this->portalAccount, $room);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::EDIT_LOCK, $member->getItemId()),
            'EDIT_LOCK is true for item types that LockManager does not support',
        );
    }

    // ---------- ITEM_FILE_LOCK ----------

    public function testFileLockReturnsTrueWhenWopiDiscoveryUnavailable(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        $this->loginAs($this->portalAccount, $room);

        // canFileLock() exits early with `return true` when DiscoveryService
        // returns null (no WOPI integration available — the default test
        // setup has no WOPI server reachable).
        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::FILE_LOCK, $material->getItemId()),
            'FILE_LOCK is granted when WOPI discovery is unavailable',
        );
    }

    // ---------- ITEM_USERROOM ----------

    public function testUserroomPrivilegeRequiresUserroomContext(): void
    {
        // Item lives in a regular project room → USERROOM check is false.
        $room = $this->createProjectRoom();
        $member = $this->createMember($this->portalAccount, $room, 2);
        $material = MaterialFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        $this->loginAs($this->portalAccount, $room);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::USERROOM, $material->getItemId()),
            'USERROOM is granted only for items inside a userroom context',
        );
    }

    public function testUserroomPrivilegeGrantedInUserroomForParticipant(): void
    {
        $userRoom = $this->createUserRoom();
        $member = $this->createMember($this->portalAccount, $userRoom, 2);
        $material = MaterialFactory::createOne([
            'room' => $userRoom,
            'creator' => $member,
        ]);

        $this->loginAs($this->portalAccount, $userRoom);

        self::assertTrue(
            $this->authChecker->isGranted(ItemVoter::USERROOM, $material->getItemId()),
            'A regular member of a userroom gets USERROOM privileges (canParticipate is true)',
        );
    }

    public function testUserroomPrivilegeDeniedForRequestedMember(): void
    {
        $userRoom = $this->createUserRoom();
        $member = $this->createMember($this->portalAccount, $userRoom, 1);
        $material = MaterialFactory::createOne([
            'room' => $userRoom,
            'creator' => $member,
        ]);

        $this->loginAs($this->portalAccount, $userRoom);

        self::assertFalse(
            $this->authChecker->isGranted(ItemVoter::USERROOM, $material->getItemId()),
            'Requested-status members fail canParticipate, so USERROOM is denied',
        );
    }

    // ---------------------------------------------------------------- setup

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }

}
