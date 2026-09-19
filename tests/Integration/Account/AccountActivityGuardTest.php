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

namespace Tests\Integration\Account;

use App\Entity\Account;
use App\Entity\Portal;
use App\Message\AccountActivityStateTransitions;
use App\MessageHandler\AccountActivityStateTransitionsHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Registry;
use Tests\Factory\AccountFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;

/**
 * The guard of the account activity workflow decides, it does not write.
 *
 * It used to roll a protected account back to `active` from inside the
 * guard. A guard is evaluated several times per transition — and also when
 * nothing is applied at all — so merely asking the workflow what is possible
 * changed the account, and whether that reached the database depended on
 * whoever flushed next. The rollback itself is still wanted; it now happens
 * in the message handler, which owns the flush.
 */
final class AccountActivityGuardTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    public function testAskingTheWorkflowLeavesTheAccountAlone(): void
    {
        $account = $this->createProtectedAccount(Account::ACTIVITY_ACTIVE_NOTIFIED);
        $stateUpdated = $account->getActivityStateUpdated()->format('Y-m-d H:i:s');

        $workflow = self::getContainer()->get(Registry::class)->get($account, 'account_activity');
        self::assertSame([], $workflow->getEnabledTransitions($account), 'the protection must block');

        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloaded = $this->reload($account);
        self::assertSame(Account::ACTIVITY_ACTIVE_NOTIFIED, $reloaded->getActivityState());
        self::assertSame(
            $stateUpdated,
            $reloaded->getActivityStateUpdated()?->format('Y-m-d H:i:s'),
            'the guard must not have touched the deadline',
        );
    }

    public function testTheHandlerRollsAProtectedAccountBack(): void
    {
        $account = $this->createProtectedAccount(Account::ACTIVITY_ACTIVE_NOTIFIED);

        $this->handle($account);

        $reloaded = $this->reload($account);
        self::assertSame(Account::ACTIVITY_ACTIVE, $reloaded->getActivityState());
        self::assertNull($reloaded->getActivityStateUpdated());
    }

    public function testTheHandlerLeavesAnAbandonedAccountAlone(): void
    {
        $account = $this->createProtectedAccount(Account::ACTIVITY_ABANDONED);

        $this->handle($account);

        self::assertSame(
            Account::ACTIVITY_ABANDONED,
            $this->reload($account)->getActivityState(),
            'abandoned is terminal — its deletion is already on its way',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    /** An account that is the only moderator of a group room. */
    private function createProtectedAccount(string $state): Account
    {
        $portal = PortalFactory::createOne(['clearInactiveAccountsFeatureEnabled' => true]);

        $account = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal->getAuthSources()->first(),
            'activityState' => $state,
            'activityStateUpdated' => new DateTime('-40 days'),
            'lastLogin' => new DateTime('-4000 days'),
            'locked' => false,
        ]);

        $groupRoom = RoomFactory::new()->groupRoom()->create([
            'portal' => $portal,
            'contextId' => $portal->getId(),
        ]);
        RoomUserFactory::new()->asModerator()->create(['account' => $account, 'room' => $groupRoom]);

        return $account;
    }

    private function handle(Account $account): void
    {
        $handler = self::getContainer()->get(AccountActivityStateTransitionsHandler::class);
        $handler(new AccountActivityStateTransitions([$account->getId()]));

        $this->entityManager->clear();
    }

    private function reload(Account $account): Account
    {
        return $this->entityManager->getRepository(Account::class)->find($account->getId());
    }
}
