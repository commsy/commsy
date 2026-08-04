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

use App\Entity\Account;
use App\Entity\Portal;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * Portal scope of the account administration.
 *
 * PORTAL_MODERATOR is granted on the portal taken from the URL and says
 * nothing about the user, account or template the same URL also names. Every
 * action here therefore asks ITEM_EDIT for its subject as well, which routes
 * through UserEditChecker and compares contexts — the portal for a portal-level
 * user item. A subject from another portal is refused.
 *
 * These tests deliberately act as a REAL portal moderator, never as root: root
 * short-circuits at the top of both voters, so a root-based test proves nothing
 * about this wiring. They also go through the real routes rather than asking the
 * authorization checker directly, because the actor ITEM_EDIT judges comes from
 * the legacy currentUserItem that LegacySubscriber primes per request — the
 * piece a voter-level test cannot cover.
 */
final class PortalSettingsPortalScopeTest extends AbstractApplicationTestCase
{
    private const PASSWORD = 'portal-scope-pw';

    private Portal $portal;
    private Account $moderator;
    private Account $member;
    private int $memberUserId;
    private int $foreignUserId;

    public function setUp(): void
    {
        parent::setUp();

        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $this->portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        $this->moderator = $this->createAccount($this->portal, $localSource, 'scope.mod');
        $this->member = $this->createAccount($this->portal, $localSource, 'scope.member');

        // A second portal with an account of its own — the out-of-scope subject.
        $foreignSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $foreignPortal = PortalFactory::createOne(['authSources' => [$foreignSource]]);
        $foreignAccount = $this->createAccount($foreignPortal, $foreignSource, 'scope.foreign');

        $this->memberUserId = $this->portalUserId($this->member);
        $this->foreignUserId = $this->portalUserId($foreignAccount);

        // Promote before logging in, so no stale cs_user_item is cached.
        $this->promoteToPortalModerator($this->moderator);
    }

    /**
     * The proof that the whole approach works: a real portal moderator, coming
     * through the real route, is granted ITEM_EDIT on a user of their own
     * portal. If LegacySubscriber resolved anything other than the portal-level
     * user item as the actor, UserEditChecker would compare mismatched contexts
     * and this would 403 — taking the entire account administration with it.
     */
    public function testPortalModeratorReachesAUserOfTheirOwnPortal(): void
    {
        $this->loginAsModerator();

        $this->client->request(
            'GET',
            "/portal/{$this->portal->getId()}/settings/accountindex/detail/{$this->memberUserId}/edit"
        );

        $this->assertResponseIsSuccessful(
            'a portal moderator must keep access to their own portal\'s accounts'
        );
    }

    /**
     * The net: every account-administration route that names a user, fed a user
     * of another portal. A new action that forgets its guard shows up here.
     *
     * @return iterable<string, array{string}>
     */
    public static function foreignSubjectRoutes(): iterable
    {
        yield 'detail'          => ['/settings/accountindex/detail/%d'];
        yield 'edit'            => ['/settings/accountindex/detail/%d/edit'];
        yield 'changeStatus'    => ['/settings/accountIndex/detail/%d/changeStatus'];
        yield 'hideMail'        => ['/settings/accountIndex/detail/%d/hidemailallwrks'];
        yield 'showMail'        => ['/settings/accountIndex/detail/%d/showmailallwroks'];
        yield 'assignWorkspace' => ['/settings/accountIndex/detail/%d/assignWorkspace'];
        yield 'deleteUser'      => ['/settings/accountindex/%d/deleteUser'];
        yield 'performAction'   => ['/settings/accountindex/%d/performUserAction/user-block'];
        yield 'sendMail'        => ['/settings/accountindex/sendmail/%d/user-account_send_mail'];
    }

    #[DataProvider('foreignSubjectRoutes')]
    public function testPortalModeratorIsRefusedAUserOfAnotherPortal(string $path): void
    {
        $this->loginAsModerator();

        // Assert the authorization decision, not its presentation: the
        // firewall's AccessDeniedHandler turns every denial into a redirect to
        // the room list, so a status-code assertion would test the handler
        // rather than the guard.
        $this->client->catchExceptions(false);
        $this->expectException(AccessDeniedException::class);

        $this->client->request(
            'GET',
            "/portal/{$this->portal->getId()}".sprintf($path, $this->foreignUserId)
        );
    }

    /**
     * Same routes, own portal — proves the guard denies by scope and not by
     * accident. Without this the test above would also pass if every route
     * simply refused everything.
     */
    #[DataProvider('foreignSubjectRoutes')]
    public function testPortalModeratorReachesTheSameRoutesForTheirOwnPortal(string $path): void
    {
        $this->loginAsModerator();

        $this->client->request(
            'GET',
            "/portal/{$this->portal->getId()}".sprintf($path, $this->memberUserId)
        );

        self::assertNotSame(
            403,
            $this->client->getResponse()->getStatusCode(),
            'the guard must not deny a subject of the moderator\'s own portal'
        );
        self::assertStringNotContainsString(
            '/room/',
            (string) $this->client->getResponse()->headers->get('Location'),
            'a redirect to the room list is what AccessDeniedHandler does on denial'
        );
    }

    // ----------------------------------------------------------------- helpers

    private function createAccount(Portal $portal, mixed $authSource, string $username): Account
    {
        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $authSource,
            'username' => $username,
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
            'plainPassword' => self::PASSWORD,
        ]);
    }

    private function loginAsModerator(): void
    {
        $this->loginAsUser($this->portal->getId(), $this->moderator->getUsername(), self::PASSWORD);
    }

    /**
     * Item id of the account's portal-level user row — what the account index
     * lists and what these routes address.
     */
    private function portalUserId(Account $account): int
    {
        $id = $this->connection()->fetchOne(
            'SELECT item_id FROM user WHERE account_id = ? AND context_id = ? AND deletion_date IS NULL',
            [$account->getId(), $account->getPortal()?->getId()]
        );
        self::assertNotFalse($id, "no portal user row for {$account->getUsername()}");

        return (int) $id;
    }

    private function promoteToPortalModerator(Account $account): void
    {
        $this->connection()->executeStatement(
            'UPDATE user SET status = 3 WHERE account_id = ? AND context_id = ?',
            [$account->getId(), $account->getPortal()?->getId()]
        );
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
