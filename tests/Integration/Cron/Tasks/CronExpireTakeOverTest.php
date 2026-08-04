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

namespace Tests\Integration\Cron\Tasks;

use App\Cron\Tasks\CronExpireTakeOver;
use App\Entity\Account;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The task's job is to end impersonation grants whose deadline has passed.
 *
 * It used to only clear the deadline, which since 1d7de0c6c (2021) achieves
 * the opposite: SwitchToUserVoter reads "allowed, no deadline" as permanently
 * allowed, so an expired grant came back unlimited on the next cron run. Before
 * that commit the timestamp WAS the grant and clearing it was correct.
 *
 * The task selects moderators (status 3) carrying a LOGIN_AS_TMSP extra, so the
 * fixtures here are portal moderators with a grant.
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class CronExpireTakeOverTest extends KernelTestCase
{
    use BootsVoter;

    public function testAnExpiredGrantIsWithdrawnAndNotMerelyUnbounded(): void
    {
        $account = $this->createPortalAccount();
        $this->promoteToPortalModerator($account);
        $this->setImpersonationGrant(
            $account,
            allowed: true,
            expiry: new DateTimeImmutable('-1 day')
        );

        $this->runCron();

        $portalUser = $this->reloadPortalUser($account);
        self::assertNull(
            $portalUser->getImpersonateExpiryDate(),
            'the deadline is cleared, as before'
        );
        self::assertFalse(
            $portalUser->getCanImpersonateAnotherUser(),
            'and the right itself is withdrawn — otherwise clearing the deadline '
            .'turns an expired grant into a permanent one'
        );
    }

    public function testAGrantThatHasNotExpiredIsLeftAlone(): void
    {
        $account = $this->createPortalAccount();
        $this->promoteToPortalModerator($account);
        $expiry = new DateTimeImmutable('+1 day');
        $this->setImpersonationGrant($account, allowed: true, expiry: $expiry);

        $this->runCron();

        $portalUser = $this->reloadPortalUser($account);
        self::assertNotNull(
            $portalUser->getImpersonateExpiryDate(),
            'a deadline in the future must survive the run'
        );
        self::assertTrue($portalUser->getCanImpersonateAnotherUser());
    }

    private function runCron(): void
    {
        self::getContainer()->get(CronExpireTakeOver::class)->run(null);
    }

    private function reloadPortalUser(Account $account): \cs_user_item
    {
        // The task writes through the legacy save() path, which does a raw
        // update without refreshing the manager caches.
        $portalId = $account->getPortal()?->getId() ?? 0;
        $userItem = $this->userService->getUserInContext($account, $portalId);
        self::assertInstanceOf(\cs_user_item::class, $userItem);
        $this->evictLegacyCache($userItem->getItemID());

        $reloaded = $this->userService->getUserInContext($account, $portalId);
        self::assertInstanceOf(\cs_user_item::class, $reloaded);

        return $reloaded;
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
