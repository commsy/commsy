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

namespace Tests\Unit\Security;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\User;
use App\Security\Authorization\Voter\ContextCreateVoter;
use App\Services\CurrentUserResolver;
use App\Utils\RequestContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Pure unit coverage of the extracted ContextCreateVoter::decide() — the
 * whole point of the extraction: the legacy decision table is verifiable
 * with no kernel, DB, request or token. Mirrors the integration suite's
 * scenarios (incl. the persisted-bool coercion quirk).
 */
final class ContextCreateVoterDecideTest extends TestCase
{
    private ContextCreateVoter $voter;

    protected function setUp(): void
    {
        // decide() uses none of these — instantiate with mocks to call it.
        $this->voter = new ContextCreateVoter(
            $this->createMock(CurrentUserResolver::class),
            $this->createMock(RequestContext::class),
            $this->createMock(RequestStack::class),
        );
    }

    public function testNullAccountIsDenied(): void
    {
        self::assertFalse($this->voter->decide($this->user(), null, false));
    }

    public function testNullUserIsDenied(): void
    {
        self::assertFalse($this->voter->decide(null, $this->account(), false));
    }

    public function testGuestUserIsDenied(): void
    {
        self::assertFalse($this->voter->decide($this->user(guest: true), $this->account(), false));
    }

    public function testRootIsAllowed(): void
    {
        self::assertTrue($this->voter->decide($this->user(root: true), $this->account(), false));
    }

    public function testPortalContextModeratorIsAllowed(): void
    {
        self::assertTrue($this->voter->decide($this->user(moderator: true), $this->account(), true));
    }

    public function testModeratorOutsidePortalContextFallsThroughToExtra(): void
    {
        // not portal context => branch 3 skipped; default 'standard' =>
        // auth source decides (createRoom=false here) => denied
        self::assertFalse(
            $this->voter->decide($this->user(moderator: true), $this->account(createRoom: false), false),
        );
    }

    public function testPerUserExtraMinusOneDenies(): void
    {
        $user = $this->user(extras: ['IS_ALLOWED_TO_CREATE_CONTEXT' => '-1']);
        self::assertFalse($this->voter->decide($user, $this->account(), false));
    }

    public function testPerUserExtraNonStandardAllows(): void
    {
        $user = $this->user(extras: ['IS_ALLOWED_TO_CREATE_CONTEXT' => '1']);
        self::assertTrue($this->voter->decide($user, $this->account(), false));
    }

    public function testPersistedBooleanFalseStillAllows(): void
    {
        // legacy quirk: false -> "" -> not 'standard', not -1 -> allow
        $user = $this->user(extras: ['IS_ALLOWED_TO_CREATE_CONTEXT' => false]);
        self::assertTrue($this->voter->decide($user, $this->account(createRoom: false), false));
    }

    public function testPersistedBooleanTrueAllows(): void
    {
        $user = $this->user(extras: ['IS_ALLOWED_TO_CREATE_CONTEXT' => true]);
        self::assertTrue($this->voter->decide($user, $this->account(createRoom: false), false));
    }

    public function testDefaultStandardFollowsAuthSourceTrue(): void
    {
        self::assertTrue($this->voter->decide($this->user(), $this->account(createRoom: true), false));
    }

    public function testDefaultStandardFollowsAuthSourceFalse(): void
    {
        self::assertFalse($this->voter->decide($this->user(), $this->account(createRoom: false), false));
    }

    public function testDefaultStandardWithoutAuthSourceIsDenied(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getAuthSource')->willReturn(null);

        self::assertFalse($this->voter->decide($this->user(), $account, false));
    }

    // ---- builders

    private function user(
        bool $guest = false,
        bool $root = false,
        bool $moderator = false,
        array $extras = [],
    ): User {
        $user = $this->createMock(User::class);
        $user->method('isGuest')->willReturn($guest);
        $user->method('isRoot')->willReturn($root);
        $user->method('isModerator')->willReturn($moderator);
        $user->method('getExtras')->willReturn($extras);

        return $user;
    }

    private function account(bool $createRoom = true): Account
    {
        $authSource = $this->createMock(AuthSource::class);
        $authSource->method('getCreateRoom')->willReturn($createRoom);

        $account = $this->createMock(Account::class);
        $account->method('getAuthSource')->willReturn($authSource);

        return $account;
    }
}
