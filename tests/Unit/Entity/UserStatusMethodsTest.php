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

namespace Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the status-derived predicates that {@see User} exposes for the new
 * App\Security\Permission services. These are pure-function checks on the
 * status int + userId string — no Doctrine state, no legacy code, fast.
 */
final class UserStatusMethodsTest extends TestCase
{
    /**
     * @return iterable<string, array{0: int, 1: string, 2: array<string, bool>}>
     */
    public static function statusMatrix(): iterable
    {
        // Each row: [status, userId, expected predicates]
        $allFalse = [
            'isGuest' => false, 'isReallyGuest' => false, 'isRequested' => false,
            'isUser' => false, 'isModerator' => false, 'isReadOnlyUser' => false,
            'isRoot' => false,
        ];

        yield 'rejected_with_real_userId' => [
            0, 'someuser',
            ['isGuest' => true] + $allFalse,
        ];
        yield 'persistent_guest_singleton' => [
            0, 'guest',
            ['isGuest' => true, 'isReallyGuest' => true] + $allFalse,
        ];
        yield 'persistent_guest_singleton_uppercase' => [
            // legacy isReallyGuest uses mb_strtolower — we mirror that.
            0, 'GUEST',
            ['isGuest' => true, 'isReallyGuest' => true] + $allFalse,
        ];
        yield 'requested_member' => [
            1, 'alice',
            ['isRequested' => true] + $allFalse,
        ];
        yield 'regular_user' => [
            2, 'bob',
            ['isUser' => true] + $allFalse,
        ];
        yield 'moderator' => [
            3, 'mod',
            ['isUser' => true, 'isModerator' => true] + $allFalse,
        ];
        yield 'root_moderator' => [
            // status=3 + userId='root' is the canonical root user_item.
            3, 'root',
            ['isUser' => true, 'isModerator' => true, 'isRoot' => true] + $allFalse,
        ];
        yield 'root_uppercase_userId' => [
            // legacy isRoot also uses mb_strtolower on user_id.
            3, 'ROOT',
            ['isUser' => true, 'isModerator' => true, 'isRoot' => true] + $allFalse,
        ];
        yield 'fake_root_with_user_status' => [
            // status=2 + userId='root' is NOT root — only status 3 counts.
            2, 'root',
            ['isUser' => true] + $allFalse,
        ];
        yield 'read_only_member' => [
            4, 'reader',
            ['isUser' => true, 'isReadOnlyUser' => true] + $allFalse,
        ];
    }

    #[DataProvider('statusMatrix')]
    public function testStatusPredicates(int $status, string $userId, array $expected): void
    {
        $user = (new User())
            ->setStatus($status)
            ->setUserId($userId);

        foreach ($expected as $method => $value) {
            self::assertSame(
                $value,
                $user->{$method}(),
                sprintf('User->%s() with status=%d userId=%s', $method, $status, $userId),
            );
        }
    }
}
