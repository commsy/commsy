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

namespace Tests\Application;

use App\Audit\AuditEvent;
use App\Entity\Account;
use App\Entity\AuditLogEntry;
use App\Repository\AuditLogEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * The acts a portal moderator performs on someone else's account are recorded
 * next to the take-overs — same log, same shape, different event.
 */
final class AccountAdminAuditTest extends AbstractApplicationTestCase
{
    public function testRaisingAnAccountToModeratorIsRecorded(): void
    {
        ['portal' => $portal, 'moderator' => $moderator, 'target' => $target] = $this->createScenario();

        $this->submitStatusForm($moderator, $target, 'moderator');

        $entry = $this->singleEntry();

        self::assertSame(AuditEvent::AccountStatusChanged, $entry->getEvent());
        self::assertSame($portal->getId(), $entry->getPortal()->getId());
        self::assertSame($target->getId(), $entry->getSubjectId());
        self::assertSame($moderator->getUsername(), $entry->getActorUsername());
        self::assertSame(['from' => 'user', 'to' => 'moderator'], $entry->getDetails());
    }

    /**
     * The same form carries the contact flag and, for root, the take-over
     * grant, so it is submitted without a status change often enough that
     * recording every submission would bury the real ones.
     */
    public function testSubmittingTheFormWithoutAStatusChangeRecordsNothing(): void
    {
        ['moderator' => $moderator, 'target' => $target] = $this->createScenario();

        $this->submitStatusForm($moderator, $target, 'user');

        self::assertSame([], $this->entries(), 'nothing changed, so nothing is recorded');
    }

    public function testSettingSomeoneElsesPasswordIsRecorded(): void
    {
        ['portal' => $portal, 'moderator' => $moderator, 'target' => $target] = $this->createScenario();
        $portalId = $portal->getId();

        $this->loginAsUser($portalId, $moderator->getUsername(), $moderator->getPlainPassword());

        $crawler = $this->client->request(
            'GET',
            sprintf('/portal/%d/settings/accountIndex/detail/%d/changePassword', $portalId, $target->getId())
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('account_index_detail_change_password[save]')->form();
        $form['account_index_detail_change_password[password][first]'] = 'aVeryFreshSecret1';
        $form['account_index_detail_change_password[password][second]'] = 'aVeryFreshSecret1';
        $this->client->submit($form);

        $entry = $this->singleEntry();

        self::assertSame(AuditEvent::AccountPasswordReset, $entry->getEvent());
        self::assertSame($target->getId(), $entry->getSubjectId());
        self::assertSame($moderator->getUsername(), $entry->getActorUsername());
        self::assertStringNotContainsString(
            'aVeryFreshSecret1',
            json_encode($entry->getDetails(), JSON_THROW_ON_ERROR),
            'a record of the act must never carry the password itself'
        );
    }

    /**
     * Withdrawing the right to take accounts over is an act over who may
     * impersonate whom, and only root can perform it.
     */
    public function testRootWithdrawingTheTakeOverRightIsRecorded(): void
    {
        ['portal' => $portal, 'target' => $target] = $this->createScenario();

        $this->loginAsRoot();
        $this->submitStatusFormAsRoot($portal->getId(), $target, ['loginIsDeactivated' => '1']);

        $entry = $this->singleEntry();

        self::assertSame(AuditEvent::AccountTakeOverGrantChanged, $entry->getEvent());
        self::assertSame($target->getId(), $entry->getSubjectId());
        self::assertSame('root', $entry->getActorUsername());
        self::assertSame(['allowed' => false], $entry->getDetails());
    }

    public function testRootTimeLimitingTheTakeOverRightIsRecorded(): void
    {
        ['portal' => $portal, 'target' => $target] = $this->createScenario();

        $this->loginAsRoot();
        $this->submitStatusFormAsRoot($portal->getId(), $target, ['impersonateExpiryDate' => '2027-03-01']);

        $entry = $this->singleEntry();

        self::assertSame(AuditEvent::AccountTakeOverGrantChanged, $entry->getEvent());
        self::assertSame(['allowed' => true, 'until' => '2027-03-01'], $entry->getDetails());
    }

    /**
     * The two fields are disabled for anyone but root, so a portal moderator
     * saving the form must not look like a change of the grant.
     */
    public function testAPortalModeratorDoesNotTouchTheTakeOverRight(): void
    {
        ['moderator' => $moderator, 'target' => $target] = $this->createScenario();

        $this->submitStatusForm($moderator, $target, 'moderator');

        $events = array_map(fn (AuditLogEntry $entry): AuditEvent => $entry->getEvent(), $this->entries());

        self::assertNotContains(AuditEvent::AccountTakeOverGrantChanged, $events);
    }

    /**
     * @return array{portal: mixed, moderator: mixed, target: mixed}
     */
    private function createScenario(): array
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        $moderator = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'portal.moderator',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $target = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'plain.member',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $this->promoteToPortalModerator($moderator);

        return ['portal' => $portal, 'moderator' => $moderator, 'target' => $target];
    }

    /**
     * The status route addresses the portal-level user row, not the account.
     */
    private function submitStatusForm(Account $moderator, Account $target, string $newStatus): void
    {
        $portalId = $moderator->getPortal()->getId();
        $this->loginAsUser($portalId, $moderator->getUsername(), $moderator->getPlainPassword());

        $crawler = $this->client->request(
            'GET',
            sprintf(
                '/portal/%d/settings/accountIndex/detail/%d/changeStatus',
                $portalId,
                $this->portalUserItemId($target)
            )
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('account_index_detail_change_status[save]')->form();
        $form['account_index_detail_change_status[newStatus]'] = $newStatus;
        $this->client->submit($form);
    }

    /**
     * Root sees two more fields on the same form. The status is left at what it
     * already is, so only the grant changes.
     *
     * @param array<string, string> $values
     */
    private function submitStatusFormAsRoot(int $portalId, Account $target, array $values): void
    {
        $crawler = $this->client->request(
            'GET',
            sprintf(
                '/portal/%d/settings/accountIndex/detail/%d/changeStatus',
                $portalId,
                $this->portalUserItemId($target)
            )
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('account_index_detail_change_status[save]')->form();
        foreach ($values as $field => $value) {
            $form["account_index_detail_change_status[{$field}]"] = $value;
        }

        $this->client->submit($form);
    }

    private function portalUserItemId(Account $account): int
    {
        return (int) static::getContainer()->get(EntityManagerInterface::class)
            ->getConnection()
            ->fetchOne(
                'SELECT item_id FROM user WHERE account_id = ? AND context_id = ?',
                [$account->getId(), $account->getPortal()?->getId()]
            );
    }

    private function promoteToPortalModerator(Account $account): void
    {
        static::getContainer()->get(EntityManagerInterface::class)
            ->getConnection()
            ->executeStatement(
                'UPDATE user SET status = 3 WHERE account_id = ? AND context_id = ?',
                [$account->getId(), $account->getPortal()?->getId()]
            );
    }

    /**
     * @return AuditLogEntry[]
     */
    private function entries(): array
    {
        return static::getContainer()->get(AuditLogEntryRepository::class)->findBy([], ['id' => 'ASC']);
    }

    private function singleEntry(): AuditLogEntry
    {
        $entries = $this->entries();
        self::assertCount(1, $entries, 'exactly one act was performed');

        return $entries[0];
    }
}
