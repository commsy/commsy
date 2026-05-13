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

use App\Entity\User;
use App\User\UserStatus;
use PHPUnit\Framework\TestCase;

final class UserStatusTest extends TestCase
{
    public function testEnumValuesMatchLegacyStatusInts(): void
    {
        self::assertSame(0, UserStatus::Guest->value);
        self::assertSame(1, UserStatus::Requested->value);
        self::assertSame(2, UserStatus::User->value);
        self::assertSame(3, UserStatus::Moderator->value);
        self::assertSame(4, UserStatus::ReadOnly->value);
    }

    public function testFromUserDelegatesToStatusInt(): void
    {
        $user = (new User())->setStatus(3);
        self::assertSame(UserStatus::Moderator, UserStatus::fromUser($user));
    }

    public function testIsMemberCoversStatusTwoToFour(): void
    {
        self::assertTrue(UserStatus::User->isMember());
        self::assertTrue(UserStatus::Moderator->isMember());
        self::assertTrue(UserStatus::ReadOnly->isMember());

        self::assertFalse(UserStatus::Guest->isMember());
        self::assertFalse(UserStatus::Requested->isMember());
    }
}
