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

namespace Tests\Unit\User;

use App\Entity\Room;
use App\Entity\User;
use App\User\UserEditChecker;
use PHPUnit\Framework\TestCase;

final class UserEditCheckerTest extends TestCase
{
    private UserEditChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserEditChecker();
    }

    // ---- canEdit

    public function testCanEditDeniesReadOnlyActor(): void
    {
        $actor = $this->user(itemId: 5, status: 4, contextId: 42, userId: 'alice');
        $target = $this->user(itemId: 6, status: 2, contextId: 42, userId: 'bob');

        self::assertFalse($this->checker->canEdit($actor, $target));
    }

    public function testCanEditGrantsRootEverywhere(): void
    {
        $actor = (new User())->setStatus(3)->setUserId('root');
        $target = $this->user(itemId: 6, status: 2, contextId: 42, userId: 'bob');

        self::assertTrue($this->checker->canEdit($actor, $target));
    }

    public function testCanEditGrantsModeratorInSameContext(): void
    {
        $actor = $this->user(itemId: 5, status: 3, contextId: 42, userId: 'alice');
        $target = $this->user(itemId: 6, status: 2, contextId: 42, userId: 'bob');

        self::assertTrue($this->checker->canEdit($actor, $target));
    }

    public function testCanEditDeniesModeratorInDifferentContext(): void
    {
        $actor = $this->user(itemId: 5, status: 3, contextId: 99, userId: 'alice');
        $target = $this->user(itemId: 6, status: 2, contextId: 42, userId: 'bob');

        self::assertFalse(
            $this->checker->canEdit($actor, $target),
            'Moderator in another context must NOT edit users in our context',
        );
    }

    public function testCanEditGrantsSelfEdit(): void
    {
        $actor = $this->user(itemId: 5, status: 2, contextId: 42, userId: 'alice', authSource: 7);
        $target = $this->user(itemId: 99, status: 2, contextId: 42, userId: 'alice', authSource: 7);

        self::assertTrue(
            $this->checker->canEdit($actor, $target),
            'Same (userId, authSource) in same context — self-edit',
        );
    }

    public function testCanEditDeniesRegularUserEditingSomeoneElse(): void
    {
        $actor = $this->user(itemId: 5, status: 2, contextId: 42, userId: 'alice');
        $target = $this->user(itemId: 6, status: 2, contextId: 42, userId: 'bob');

        self::assertFalse($this->checker->canEdit($actor, $target));
    }

    private function user(
        int $itemId,
        int $status,
        int $contextId,
        string $userId,
        int $authSource = 1,
    ): User {
        $u = (new User())
            ->setStatus($status)
            ->setUserId($userId)
            ->setAuthSource($authSource);
        $u->itemId = $itemId;
        $u->setRoom((new Room())->setItemId($contextId));
        return $u;
    }
}
