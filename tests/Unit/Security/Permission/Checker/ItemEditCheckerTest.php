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

namespace Tests\Unit\Security\Permission\Checker;

use App\Entity\Account;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use App\Lock\LockManager;
use App\Repository\UserRepository;
use App\Security\Permission\Checker\ItemEditChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ItemEditCheckerTest extends TestCase
{
    private LockManager&MockObject $lockManager;
    private UserRepository&MockObject $userRepository;
    private ItemEditChecker $checker;

    protected function setUp(): void
    {
        $this->lockManager = $this->createMock(LockManager::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->checker = new ItemEditChecker($this->lockManager, $this->userRepository);
    }

    public function testReturnsTrueWhenItemTypeDoesNotSupportLocking(): void
    {
        $this->lockManager->method('supportsLocking')->with(7)->willReturn(false);
        $this->lockManager->expects(self::never())->method('userCanLock');

        self::assertTrue($this->checker->canEditLock(7));
    }

    public function testDelegatesToUserCanLockWhenLockingIsSupported(): void
    {
        $this->lockManager->method('supportsLocking')->with(7)->willReturn(true);
        $this->lockManager->expects(self::once())
            ->method('userCanLock')
            ->with(7)
            ->willReturn(true);

        self::assertTrue($this->checker->canEditLock(7));
    }

    public function testReturnsFalseWhenAnotherUserHoldsTheLock(): void
    {
        $this->lockManager->method('supportsLocking')->willReturn(true);
        $this->lockManager->method('userCanLock')->willReturn(false);

        self::assertFalse($this->checker->canEditLock(7));
    }

    // ---- canEdit (full base impl, mirrors cs_item::mayEdit body)

    public function testCanEditDeniesReadOnlyUser(): void
    {
        $actor = $this->user(itemId: 5, status: 4);
        $item = $this->materials(itemId: 7, contextId: 42);

        self::assertFalse($this->checker->canEdit($actor, $item));
    }

    public function testCanEditGrantsRootRegardlessOfContext(): void
    {
        $actor = (new User())->setStatus(3)->setUserId('root');
        $item = $this->materials(itemId: 7, contextId: 42);

        self::assertTrue($this->checker->canEdit($actor, $item));
    }

    public function testCanEditDeniesWhenActorHasNoMembershipInContext(): void
    {
        $actor = $this->user(itemId: 5, status: 2, contextId: 99);
        $actor->setAccount(new Account());
        $this->userRepository->method('findInContext')->willReturn(null);

        $item = $this->materials(itemId: 7, contextId: 42);

        self::assertFalse($this->checker->canEdit($actor, $item));
    }

    public function testCanEditGrantsModeratorInContextIfLockOk(): void
    {
        $actor = $this->user(itemId: 5, status: 3, contextId: 42);
        $item = $this->materials(itemId: 7, contextId: 42);

        $this->lockManager->method('supportsLocking')->willReturn(true);
        $this->lockManager->method('userCanLock')->willReturn(true);

        self::assertTrue($this->checker->canEdit($actor, $item));
    }

    public function testCanEditGrantsCreatorInContext(): void
    {
        $creator = $this->user(itemId: 99);
        $item = $this->materials(itemId: 7, contextId: 42);
        $item->setCreator($creator);

        $actor = $this->user(itemId: 99, status: 2, contextId: 42);

        $this->lockManager->method('supportsLocking')->willReturn(false);

        self::assertTrue($this->checker->canEdit($actor, $item));
    }

    public function testCanEditGrantsPublicItemForAnyMember(): void
    {
        $actor = $this->user(itemId: 5, status: 2, contextId: 42);
        $item = $this->materials(itemId: 7, contextId: 42);
        $item->setPublic(true);  // public=1 → !isPrivateEditing

        $this->lockManager->method('supportsLocking')->willReturn(false);

        self::assertTrue($this->checker->canEdit($actor, $item));
    }

    public function testCanEditDeniesNonCreatorOnPrivateItem(): void
    {
        $actor = $this->user(itemId: 5, status: 2, contextId: 42);
        $creator = $this->user(itemId: 99);
        $item = $this->materials(itemId: 7, contextId: 42);
        $item->setCreator($creator);
        $item->setPublic(false);  // private editing on

        self::assertFalse($this->checker->canEdit($actor, $item));
    }

    private function user(int $itemId, int $status = 2, ?int $contextId = null): User
    {
        $u = (new User())->setStatus($status);
        $u->itemId = $itemId;
        $u->userId = 'user-' . $itemId;
        if ($contextId !== null) {
            $u->setRoom((new Room())->setItemId($contextId));
        }
        return $u;
    }

    private function materials(int $itemId, int $contextId): Materials
    {
        $m = new Materials();
        $idProp = new ReflectionProperty(Materials::class, 'itemId');
        $idProp->setValue($m, $itemId);
        $m->setContextId($contextId);
        return $m;
    }
}
