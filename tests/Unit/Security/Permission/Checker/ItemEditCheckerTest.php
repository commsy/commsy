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

use App\Lock\LockManager;
use App\Security\Permission\Checker\ItemEditChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ItemEditCheckerTest extends TestCase
{
    private LockManager&MockObject $lockManager;
    private ItemEditChecker $checker;

    protected function setUp(): void
    {
        $this->lockManager = $this->createMock(LockManager::class);
        $this->checker = new ItemEditChecker($this->lockManager);
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
}
