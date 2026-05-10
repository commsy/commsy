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

namespace Tests\Integration\Repository;

use App\Entity\Account;
use App\Repository\AccountsRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\PortalFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
final class AccountsRepositoryTest extends KernelTestCase
{
    private AccountsRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(AccountsRepository::class);
    }

    // ---- findOneByCredentials

    public function testFindOneByCredentialsReturnsTheMatchingAccount(): void
    {
        $account = AccountStory::get('account');

        $found = $this->repository->findOneByCredentials(
            $account->getUsername(),
            $account->getPortal(),
            $account->getAuthSource(),
        );

        self::assertInstanceOf(Account::class, $found);
        self::assertSame($account->getId(), $found->getId());
    }

    public function testFindOneByCredentialsReturnsNullForUnknownUsername(): void
    {
        $account = AccountStory::get('account');

        self::assertNull($this->repository->findOneByCredentials(
            'this-user-does-not-exist',
            $account->getPortal(),
            $account->getAuthSource(),
        ));
    }

    public function testFindOneByCredentialsArrayDelegates(): void
    {
        $account = AccountStory::get('account');

        $found = $this->repository->findOneByCredentialsArray([
            'username' => $account->getUsername(),
            'portal' => $account->getPortal(),
            'authSource' => $account->getAuthSource(),
        ]);

        self::assertSame($account->getId(), $found?->getId());
    }

    // ---- findByEmailAndPortalId

    public function testFindByEmailAndPortalIdReturnsAccountsWithThatEmail(): void
    {
        $account = AccountStory::get('account');

        $results = $this->repository->findByEmailAndPortalId(
            $account->getEmail(),
            $account->getPortal()->getId(),
        );

        self::assertNotEmpty($results);
        $ids = array_map(static fn (Account $a): int => $a->getId(), $results);
        self::assertContains($account->getId(), $ids);
    }

    public function testFindByEmailAndPortalIdReturnsEmptyForUnknownEmail(): void
    {
        $account = AccountStory::get('account');

        self::assertSame(
            [],
            $this->repository->findByEmailAndPortalId('nope@example.test', $account->getPortal()->getId()),
        );
    }

    // ---- findAllExceptRoot

    public function testFindAllExceptRootExcludesTheRootAccount(): void
    {
        $portal = AccountStory::get('account')->getPortal();
        AccountFactory::createOne([
            'username' => 'root',
            'portal' => $portal,
            'authSource' => $portal->getAuthSources()->first(),
        ]);

        $results = $this->repository->findAllExceptRoot();
        $usernames = array_map(static fn (Account $a): string => $a->getUsername(), $results);

        self::assertNotContains('root', $usernames);
    }

    // ---- countByPortal

    public function testCountByPortalGroupsByLivePortals(): void
    {
        // Includes the AccountStory portal + its Account.
        $rows = $this->repository->countByPortal();

        self::assertNotEmpty($rows);
        foreach ($rows as $row) {
            self::assertGreaterThanOrEqual(1, (int) $row['count']);
        }
    }

    // ---- updateActivity

    public function testUpdateActivityFlipsAccountActivityState(): void
    {
        $portal = AccountStory::get('account')->getPortal();
        $account = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal->getAuthSources()->first(),
            'activityState' => 'active',
        ]);

        $this->repository->updateActivity('active', 'idle');

        // Bulk update bypasses Doctrine's UoW — clear + reload.
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $reloaded = $em->getRepository(Account::class)->find($account->getId());

        self::assertSame('idle', $reloaded->getActivityState());
    }
}
