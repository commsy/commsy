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
use App\Audit\AuditSubjectType;
use App\Entity\Account;
use App\Entity\AuditLogEntry;
use App\Repository\AuditLogEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * Every account take-over has to leave a record, and only a take-over may.
 *
 * The recorder sits on the firewall's switch event rather than on the portal
 * settings route, so these tests drive it both ways: through the route a
 * moderator uses and through the bare parameter, which works on any url.
 */
final class TakeOverAuditTest extends AbstractApplicationTestCase
{
    public function testTakingOverAnAccountIsRecorded(): void
    {
        ['portal' => $portal, 'moderator' => $moderator, 'target' => $target] = $this->createScenario();

        $this->takeOverThroughTheSettingsRoute($moderator, $target);

        $entry = $this->singleEntry();

        self::assertSame(AuditEvent::AccountTakeOver, $entry->getEvent());
        self::assertSame($portal->getId(), $entry->getPortal()->getId(), 'the entry belongs to the portal of the account taken over');
        self::assertSame(AuditSubjectType::Account, $entry->getSubjectType());
        self::assertSame($target->getId(), $entry->getSubjectId());
        self::assertSame($moderator->getUsername(), $entry->getActorUsername(), 'the moderator is answerable, not the account being worn');
        self::assertSame($target->getUsername(), $entry->getSubjectLabel());
    }

    /**
     * The names are copied so the entry still says something after the account
     * is gone — the reason both parties are stored twice.
     */
    public function testTheRecordKeepsBothNamesOutsideTheAccountRows(): void
    {
        ['portal' => $portal, 'moderator' => $moderator, 'target' => $target] = $this->createScenario();

        $this->takeOverThroughTheSettingsRoute($moderator, $target);

        $entry = $this->singleEntry();

        self::assertSame(
            trim($moderator->getFirstname() . ' ' . $moderator->getLastname()),
            $entry->getActorName()
        );
        self::assertSame(
            trim($target->getFirstname() . ' ' . $target->getLastname()),
            $entry->getSubjectName()
        );
    }

    /**
     * The bare parameter is honoured on any url under the firewall, so a
     * recorder bound to the settings route would miss this one.
     */
    public function testTakingOverThroughTheBareParameterIsRecordedToo(): void
    {
        ['portal' => $portal, 'moderator' => $moderator, 'target' => $target] = $this->createScenario();
        $portalId = $portal->getId();

        $this->loginAsUser($portalId, $moderator->getUsername(), $moderator->getPlainPassword());
        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user={$target->getUsername()}");

        self::assertSame($target->getId(), $this->singleEntry()->getSubjectId());
    }

    /**
     * Ending a take-over raises the same event, carrying the restored original
     * token. Recording it would read as the taken-over account taking over the
     * moderator.
     */
    public function testEndingATakeOverIsNotRecorded(): void
    {
        ['moderator' => $moderator, 'target' => $target] = $this->createScenario();

        $this->takeOverThroughTheSettingsRoute($moderator, $target);
        $this->client->request('GET', '/takeover/end?_switch_user=_exit');

        self::assertCount(1, $this->entries(), 'the way back is not a take-over');
    }

    /**
     * The point of naming the actor rather than the token holder: an act
     * performed while wearing someone else's account is answered for by the
     * person who started the take-over, and the worn account is named beside
     * it. Otherwise the log would blame the account that was taken over.
     */
    public function testAnActPerformedDuringATakeOverNamesThePersonBehindIt(): void
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);
        $portalId = $portal->getId();

        $actor = $this->createAccount($portal, $localSource, 'first.moderator');
        $wornModerator = $this->createAccount($portal, $localSource, 'second.moderator');
        $victim = $this->createAccount($portal, $localSource, 'plain.member');

        $this->promoteToPortalModerator($actor);
        $this->promoteToPortalModerator($wornModerator);

        $this->takeOverThroughTheSettingsRoute($actor, $wornModerator);

        // Now acting as the second moderator: set a third account's password.
        $crawler = $this->client->request(
            'GET',
            sprintf('/portal/%d/settings/accountIndex/detail/%d/changePassword', $portalId, $victim->getId())
        );
        $form = $crawler->selectButton('account_index_detail_change_password[save]')->form();
        $form['account_index_detail_change_password[password][first]'] = 'aVeryFreshSecret1';
        $form['account_index_detail_change_password[password][second]'] = 'aVeryFreshSecret1';
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $entries = $this->entries();
        self::assertCount(2, $entries, 'the take-over and the password reset');

        $passwordEntry = $entries[1];
        self::assertSame(AuditEvent::AccountPasswordReset, $passwordEntry->getEvent());
        self::assertSame('first.moderator', $passwordEntry->getActorUsername(), 'the person behind the act');
        self::assertSame('second.moderator', $passwordEntry->getDetails()['acting_as'] ?? null);
    }

    public function testARefusedTakeOverIsNotRecorded(): void
    {
        ['portal' => $portal, 'moderator' => $plainMember, 'target' => $target] = $this->createScenario(promote: false);
        $portalId = $portal->getId();

        $this->loginAsUser($portalId, $plainMember->getUsername(), $plainMember->getPlainPassword());
        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user={$target->getUsername()}");

        self::assertSame([], $this->entries(), 'nothing happened, so nothing is recorded');
    }

    /**
     * @return array{portal: mixed, moderator: mixed, target: mixed}
     */
    private function createScenario(bool $promote = true): array
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        $moderator = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'portal.moderator',
            'firstname' => 'Mona',
            'lastname' => 'Moderator',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $target = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'takeover.target',
            'firstname' => 'Tarek',
            'lastname' => 'Target',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        if ($promote) {
            $this->promoteToPortalModerator($moderator);
        }

        return ['portal' => $portal, 'moderator' => $moderator, 'target' => $target];
    }

    private function createAccount(mixed $portal, mixed $authSource, string $username): Account
    {
        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $authSource,
            'username' => $username,
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);
    }

    private function takeOverThroughTheSettingsRoute(Account $moderator, Account $target): void
    {
        $portalId = $moderator->getPortal()->getId();
        $this->loginAsUser($portalId, $moderator->getUsername(), $moderator->getPlainPassword());

        // The firewall answers `_switch_user` with a redirect, so the switch
        // only takes effect on the follow-up request — same as in a browser.
        $this->client->followRedirects();
        $this->client->request(
            'GET',
            sprintf('/portal/%d/settings/accountIndex/detail/%d/takeOver', $portalId, $target->getId())
        );

        self::assertSame(
            $target->getUsername(),
            $this->authenticatedUsername(),
            'precondition: the take-over must work'
        );
    }

    private function authenticatedUsername(): ?string
    {
        $user = static::getContainer()->get('security.token_storage')->getToken()?->getUser();

        return $user instanceof Account ? $user->getUsername() : null;
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
        self::assertCount(1, $entries, 'exactly one take-over happened');

        return $entries[0];
    }
}
