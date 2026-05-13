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

    public function testDefaultUserCanSwitchBecauseImpersonationIsEnabledByDefault(): void
    {
        // Counter-intuitive characterization: a freshly-created Account
        // has NO 'DEACTIVATE_LOGIN_AS' extra, so getCanImpersonateAnotherUser()
        // returns true. With no expiry set, the voter grants CAN_SWITCH_USER.
        // Phase 2 should consider whether this default is actually intended,
        // but for now we pin it.
        $this->loginAs($this->portalAccount);
        $target = $this->createPortalAccount();

        self::assertTrue(
            $this->authChecker->isGranted('CAN_SWITCH_USER', $target),
            'cs_user_item::getCanImpersonateAnotherUser defaults to TRUE (extra absent → allowed)',
        );
    }

    public function testDeactivateLoginAsBlocksSwitch(): void
    {
        $this->setImpersonationGrant($this->portalAccount, allowed: false);
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
