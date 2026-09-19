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

use App\Account\AccountManager;
use App\Account\LastModeratorChecker;
use App\Entity\Account;
use App\Entity\Portal;
use App\Message\AccountActivityStateTransitions;
use App\MessageHandler\AccountActivityStateTransitionsHandler;
use App\Repository\AccountsRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;
use Tests\Factory\AccountFactory;
use Tests\Factory\PortalFactory;

/**
 * A single account that blows up must not take its batch down with it.
 *
 * Before, one failure aborted the whole message: the mails already sent in
 * that run had gone out, but no state was ever stored, and messenger retried
 * the message — so the same people were mailed again on every attempt, night
 * after night.
 *
 * The workflow is wrapped rather than replaced: it does the real work for
 * every account and throws for one of them, which is how a broken mail
 * template or an unexpected data shape shows up in production.
 */
final class HandlerIsolatesFailuresTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    public function testOneFailingAccountDoesNotStopTheBatch(): void
    {
        $portal = PortalFactory::createOne(['clearInactiveAccountsFeatureEnabled' => true]);

        $first = $this->createOverdueAccount($portal);
        $broken = $this->createOverdueAccount($portal);
        $last = $this->createOverdueAccount($portal);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $handler = new AccountActivityStateTransitionsHandler(
            self::getContainer()->get(AccountsRepository::class),
            $this->entityManager,
            $this->workflowThrowingFor($broken),
            self::getContainer()->get(LastModeratorChecker::class),
            self::getContainer()->get(AccountManager::class),
            $logger,
        );

        $handler(new AccountActivityStateTransitions([
            $first->getId(),
            $broken->getId(),
            $last->getId(),
        ]));

        $this->entityManager->clear();

        self::assertSame(
            Account::ACTIVITY_ACTIVE_NOTIFIED,
            $this->reload($first)->getActivityState(),
            'the account handled before the failing one must keep its transition',
        );
        self::assertSame(
            Account::ACTIVITY_ACTIVE_NOTIFIED,
            $this->reload($last)->getActivityState(),
            'the account after the failing one must still be processed',
        );
        self::assertSame(
            Account::ACTIVITY_ACTIVE,
            $this->reload($broken)->getActivityState(),
            'the failing account itself stays where it was',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    /** The real state machine, except that it throws for one account. */
    private function workflowThrowingFor(Account $broken): WorkflowInterface
    {
        $real = self::getContainer()->get(Registry::class)->get($broken, 'account_activity');
        $brokenId = $broken->getId();

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('getEnabledTransitions')->willReturnCallback(
            function (Account $account) use ($real, $brokenId): array {
                if ($account->getId() === $brokenId) {
                    throw new RuntimeException('mail template blew up');
                }

                return $real->getEnabledTransitions($account);
            }
        );
        $workflow->method('can')->willReturnCallback(
            fn (Account $account, string $transition): bool => $real->can($account, $transition)
        );
        $workflow->method('apply')->willReturnCallback(
            fn (Account $account, string $transition) => $real->apply($account, $transition)
        );

        return $workflow;
    }

    private function createOverdueAccount(Portal $portal): Account
    {
        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal->getAuthSources()->first(),
            'activityState' => Account::ACTIVITY_ACTIVE,
            'activityStateUpdated' => null,
            'lastLogin' => new DateTime('-4000 days'),
            'locked' => false,
        ]);
    }

    private function reload(Account $account): Account
    {
        return $this->entityManager->getRepository(Account::class)->find($account->getId());
    }
}
