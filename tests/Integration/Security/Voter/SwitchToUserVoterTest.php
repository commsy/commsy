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

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for SwitchToUserVoter (CAN_SWITCH_USER).
 *
 *   true iff: actor.username === 'root',
 *         OR: portalUser.getCanImpersonateAnotherUser() AND grant not expired
 *
 * Important inversion: cs_user_item::getCanImpersonateAnotherUser() returns
 * `!_issetExtra('DEACTIVATE_LOGIN_AS')` — i.e. the default is TRUE (allowed).
 * To block impersonation you set the DEACTIVATE_LOGIN_AS extra.
 *
 * Expiry is stored as 'LOGIN_AS_TMSP' (ISO 8601 ATOM string).
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class SwitchToUserVoterTest extends KernelTestCase
{
    use BootsVoter;

    public function testRootCanSwitchToAnyUser(): void
    {
        $rootAccount = $this->createPortalAccount('root');
        $this->actAsRoot($rootAccount);

        $target = $this->createPortalAccount();

        self::assertTrue($this->authChecker->isGranted('CAN_SWITCH_USER', $target));
    }

    /**
     * The opt-out default alone is not enough any more. getCanImpersonate-
     * AnotherUser() still returns true for a fresh account — the extra is
     * absent — but taking an account over is a moderation act, so the voter
     * additionally requires moderator status. A plain member is refused.
     */
    public function testPlainMemberCannotSwitchDespiteThePermissiveDefault(): void
    {
        $this->loginAs($this->portalAccount);
        $target = $this->createPortalAccount();

        self::assertFalse(
            $this->authChecker->isGranted('CAN_SWITCH_USER', $target),
            'the opt-out default grants nothing without portal moderator status',
        );
    }

    public function testPortalModeratorCanSwitchByDefault(): void
    {
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);
        $target = $this->createPortalAccount();

        self::assertTrue(
            $this->authChecker->isGranted('CAN_SWITCH_USER', $target),
            'a portal moderator holds the right unless it was withdrawn',
        );
    }

    /**
     * The target has to live in the moderator's own portal. UserProvider
     * already resolves portal-scoped, but the voter states it itself so any
     * future caller is covered.
     */
    public function testPortalModeratorCannotSwitchToAnAccountOfAnotherPortal(): void
    {
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        $foreign = $this->createPortalAccount(portal: PortalFactory::createOne());

        self::assertFalse(
            $this->authChecker->isGranted('CAN_SWITCH_USER', $foreign),
            'portal moderation stops at the portal boundary here too',
        );
    }

    public function testDeactivateLoginAsBlocksSwitch(): void
    {
        $this->setImpersonationGrant($this->portalAccount, allowed: false);
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        $target = $this->createPortalAccount();

        self::assertFalse(
            $this->authChecker->isGranted('CAN_SWITCH_USER', $target),
            'Disabled impersonation flag (DEACTIVATE_LOGIN_AS extra) blocks switching',
        );
    }

    public function testExpiredImpersonationGrantBlocksSwitch(): void
    {
        $this->setImpersonationGrant(
            $this->portalAccount,
            allowed: true,
            expiry: new \DateTimeImmutable('-1 day'),
        );
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        $target = $this->createPortalAccount();

        self::assertFalse(
            $this->authChecker->isGranted('CAN_SWITCH_USER', $target),
            'A LOGIN_AS_TMSP value in the past blocks impersonation',
        );
    }

    public function testFutureImpersonationGrantAllowsSwitch(): void
    {
        $this->setImpersonationGrant(
            $this->portalAccount,
            allowed: true,
            expiry: new \DateTimeImmutable('+1 day'),
        );
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        $target = $this->createPortalAccount();

        self::assertTrue(
            $this->authChecker->isGranted('CAN_SWITCH_USER', $target),
            'A LOGIN_AS_TMSP in the future is still a valid grant',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
