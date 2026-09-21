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

namespace Tests\Application\Controller;

use App\Audit\AuditEvent;
use App\Audit\AuditSubjectType;
use App\Entity\Account;
use App\Entity\AuditLogEntry;
use App\Entity\Portal;
use App\Repository\AuditLogEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * The audit log view in the portal configuration.
 *
 * What it must never do is leak: the log names people and what was done to
 * them, so it is readable by the moderation of that one portal and by nobody
 * else, and it shows that portal's entries only.
 */
final class AuditLogViewTest extends AbstractApplicationTestCase
{
    public function testPortalModeratorSeesTheEntriesOfTheirPortal(): void
    {
        ['portal' => $portal, 'moderator' => $moderator] = $this->createScenario();
        $this->recordEntry($portal, AuditEvent::AccountTakeOver, 'takeover.target', 'Tarek Target');

        $this->loginAsUser($portal->getId(), $moderator->getUsername(), $moderator->getPlainPassword());
        $crawler = $this->client->request('GET', "/portal/{$portal->getId()}/settings/auditLog");

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('takeover.target', $crawler->filter('table')->text());
    }

    /**
     * The portal configuration navigation is the only way to the log, so the
     * entry has to be there. Narrowing to a single kind of event is the
     * filter's job, not the menu's.
     */
    public function testTheNavigationOffersTheLog(): void
    {
        ['portal' => $portal, 'moderator' => $moderator] = $this->createScenario();

        $this->loginAsUser($portal->getId(), $moderator->getUsername(), $moderator->getPlainPassword());
        $crawler = $this->client->request('GET', "/portal/{$portal->getId()}/settings/auditLog");

        $hrefs = $crawler->filter('#sidebar_left a')->each(fn ($node): string => (string) $node->attr('href'));

        self::assertContains("/portal/{$portal->getId()}/settings/auditLog", $hrefs);
    }

    public function testEntriesOfAnotherPortalAreNotShown(): void
    {
        ['portal' => $portal, 'moderator' => $moderator] = $this->createScenario();
        ['portal' => $foreignPortal] = $this->createScenario('foreign');
        $this->recordEntry($foreignPortal, AuditEvent::AccountTakeOver, 'foreign.target', 'Fara Foreign');

        $this->loginAsUser($portal->getId(), $moderator->getUsername(), $moderator->getPlainPassword());
        $this->client->request('GET', "/portal/{$portal->getId()}/settings/auditLog");

        $this->assertResponseIsSuccessful();
        self::assertStringNotContainsString('foreign.target', (string) $this->client->getResponse()->getContent());
    }

    /**
     * A refusal does not surface as 403 here: App\Security\AccessDeniedHandler
     * turns it into a redirect for anything but an xhr request. What matters is
     * that the log is not rendered.
     */
    public function testAPlainMemberIsRefused(): void
    {
        ['portal' => $portal, 'member' => $member] = $this->createScenario();
        $this->recordEntry($portal, AuditEvent::AccountTakeOver, 'takeover.target', 'Tarek Target');

        $this->loginAsUser($portal->getId(), $member->getUsername(), $member->getPlainPassword());
        $this->client->request('GET', "/portal/{$portal->getId()}/settings/auditLog");

        // Their own portal is the context they are already in, so there is no
        // room left to bounce to: the refusal is answered instead.
        self::assertResponseStatusCodeSame(403);
        self::assertStringNotContainsString('takeover.target', (string) $this->client->getResponse()->getContent());
    }

    public function testAModeratorOfAnotherPortalIsRefused(): void
    {
        ['portal' => $portal] = $this->createScenario();
        $this->recordEntry($portal, AuditEvent::AccountTakeOver, 'takeover.target', 'Tarek Target');
        ['portal' => $foreignPortal, 'moderator' => $foreignModerator] = $this->createScenario('foreign');

        $this->loginAsUser(
            $foreignPortal->getId(),
            $foreignModerator->getUsername(),
            $foreignModerator->getPlainPassword()
        );
        $this->client->request('GET', "/portal/{$portal->getId()}/settings/auditLog");

        self::assertResponseRedirects();
        self::assertStringNotContainsString('takeover.target', (string) $this->client->getResponse()->getContent());
    }

    /**
     * Picking a single kind of event is how a moderator gets from the whole log
     * to the take-overs, so the filter has to actually narrow the list.
     */
    public function testTheEventFilterNarrowsTheList(): void
    {
        ['portal' => $portal, 'moderator' => $moderator] = $this->createScenario();
        $this->recordEntry($portal, AuditEvent::AccountTakeOver, 'takeover.target', 'Tarek Target');
        $this->recordEntry($portal, AuditEvent::AccountPasswordReset, 'password.target', 'Pia Password');

        $this->loginAsUser($portal->getId(), $moderator->getUsername(), $moderator->getPlainPassword());
        $this->client->request(
            'GET',
            "/portal/{$portal->getId()}/settings/auditLog?audit_log_filter[event]=" . AuditEvent::AccountTakeOver->value
        );

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('takeover.target', $content);
        self::assertStringNotContainsString('password.target', $content);
    }

    /**
     * @return array{portal: mixed, moderator: mixed, member: mixed}
     */
    private function createScenario(string $prefix = 'home'): array
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        $moderator = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => $prefix . '.moderator',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $member = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => $prefix . '.member',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $this->promoteToPortalModerator($moderator);

        return ['portal' => $portal, 'moderator' => $moderator, 'member' => $member];
    }

    private function recordEntry(Portal $portal, AuditEvent $event, string $subjectLabel, string $subjectName): void
    {
        static::getContainer()->get(AuditLogEntryRepository::class)->add(new AuditLogEntry(
            portal: $portal,
            event: $event,
            subjectType: AuditSubjectType::Account,
            subjectId: null,
            subjectLabel: $subjectLabel,
            subjectName: $subjectName,
        ));
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
}
