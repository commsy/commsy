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

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * Ending an account take-over must return the moderator to their own login
 * instead of signing them out.
 *
 * Where {@see SwitchUserTest} pins who may switch, these tests pin the way back.
 * The firewall answers `_switch_user=_exit` with a redirect to the same url
 * without the parameter, so the route the exit link points at decides what
 * happens next: a plain route keeps the restored login, the logout route throws
 * it away again.
 */
final class TakeoverExitTest extends AbstractApplicationTestCase
{
    /**
     * Follows the account menu entry itself rather than a hand-written url, so
     * this fails if the link is ever pointed back at the logout route.
     */
    public function testEndingATakeOverThroughTheAccountMenuRestoresTheOriginalLogin(): void
    {
        $this->createScenarioAndTakeOver();

        $exitLink = $this->client->getCrawler()->filter('a[href*="_switch_user=_exit"]')->first()->link();
        $this->client->click($exitLink);

        self::assertSame('portal.moderator', $this->authenticatedUsername(), 'the moderator must be signed in again');
        $this->assertResponseIsSuccessful();
    }

    public function testEndingATakeOverClearsTheTakeOverSessionValues(): void
    {
        $this->createScenarioAndTakeOver();

        $session = $this->client->getRequest()->getSession();
        self::assertNotNull($session->get('takeover_context'), 'precondition: a take-over sets its fallbacks');
        self::assertNotNull($session->get('takeover_authSourceId'), 'precondition: a take-over sets its fallbacks');

        $this->client->request('GET', '/takeover/end?_switch_user=_exit');

        $session = $this->client->getRequest()->getSession();
        self::assertNull($session->get('takeover_context'), 'UserProvider must not keep consulting the fallbacks');
        self::assertNull($session->get('takeover_authSourceId'));
    }

    public function testExitLinkDoesNotPointAtTheLogoutRoute(): void
    {
        $this->createScenarioAndTakeOver();

        // The profile menu only renders inside a room context, so this inspects
        // the page the take-over itself landed on.
        $hrefs = $this->client->getCrawler()->filter('a[href*="_switch_user=_exit"]')
            ->each(fn ($node): string => (string) $node->attr('href'));

        self::assertNotEmpty($hrefs, 'the account menu must offer a way out of the take-over');
        foreach ($hrefs as $href) {
            self::assertStringNotContainsString('/logout', $href);
        }
    }

    /**
     * Logs in as a portal moderator and takes over an account of that portal —
     * the flow the exit link exists for.
     */
    private function createScenarioAndTakeOver(): void
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
            'username' => 'takeover.target',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $this->promoteToPortalModerator($moderator);
        $this->loginAsUser($portal->getId(), $moderator->getUsername(), $moderator->getPlainPassword());

        // The firewall answers `_switch_user` with a redirect, so the switch only
        // takes effect on the follow-up request — same as in a browser.
        $this->client->followRedirects();

        $this->client->request(
            'GET',
            sprintf('/portal/%d/settings/accountIndex/detail/%d/takeOver', $portal->getId(), $target->getId())
        );

        self::assertSame('takeover.target', $this->authenticatedUsername(), 'precondition: the take-over must work');
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

    private function authenticatedUsername(): ?string
    {
        $token = static::getContainer()->get('security.token_storage')->getToken();
        $user = $token?->getUser();

        return $user instanceof Account ? $user->getUsername() : null;
    }
}
