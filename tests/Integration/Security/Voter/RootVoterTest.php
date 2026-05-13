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

use App\Security\Authorization\Voter\RootVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for RootVoter.
 *
 *   ROLE_ROOT → Account.userIdentifier === 'root'
 *
 * The voter has just one branch and no legacy delegation. Tests pin the
 * positive (root) and negative (non-root) outcomes plus the
 * not-instanceof-UserInterface guard (no token).
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class RootVoterTest extends KernelTestCase
{
    use BootsVoter;

    public function testRootAccountIsGrantedRoleRoot(): void
    {
        $rootAccount = $this->createPortalAccount('root');
        $this->actAsRoot($rootAccount);

        self::assertTrue($this->authChecker->isGranted(RootVoter::ROOT));
    }

    public function testRegularAccountIsNotGrantedRoleRoot(): void
    {
        $this->setSecurityToken($this->portalAccount);

        self::assertFalse(
            $this->authChecker->isGranted(RootVoter::ROOT),
            'A non-"root" account never gets ROLE_ROOT regardless of cs_user_item status',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
