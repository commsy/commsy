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
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

/**
 * Access-control tests for Symfony's switch_user listener, which is enabled on
 * the main firewall with `role: CAN_SWITCH_USER` and therefore reacts to a
 * `?_switch_user=` parameter on ANY url under that firewall — not only on the
 * portal settings take-over route.
 *
 * These tests answer the question a voter test cannot: does the parameter
 * actually change the authenticated identity end to end?
 */
final class SwitchUserTest extends AbstractApplicationTestCase
{
    /**
     * The take-over path a portal moderator is meant to use, exercised without
     * the settings route: the parameter alone switches identity.
     */
    public function testSwitchUserParameterChangesTheAuthenticatedIdentity(): void
    {
        ['portal' => $portal, 'actor' => $actor, 'target' => $target] = $this->createScenario();
        $portalId = $portal->getId();

        $this->loginAsUser($portalId, $actor->getUsername(), $actor->getPlainPassword());

        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user={$target->getUsername()}");

        self::assertSame(
            $target->getUsername(),
            $this->authenticatedUsername(),
            'the _switch_user parameter is honoured outside the portal settings route',
        );
    }

    /**
     * Characterization of a permissive default, NOT an endorsement.
     *
     * `cs_user_item::getCanImpersonateAnotherUser()` is an opt-OUT
     * (`!_issetExtra('DEACTIVATE_LOGIN_AS')`), and SwitchToUserVoter checks
     * nothing else — no moderator status, no portal-moderation right. A plain
     * portal member therefore passes CAN_SWITCH_USER, and because the listener
     * runs on every url the settings route's PORTAL_MODERATOR guard does not
     * contain it.
     *
     * If this default is tightened, this test is the one that must flip to
     * asserting the actor stays themselves.
     */
    public function testPlainMemberCanImpersonateAnotherMemberBecauseTheDefaultIsPermissive(): void
    {
        ['portal' => $portal, 'actor' => $actor, 'target' => $target] = $this->createScenario();
        $portalId = $portal->getId();

        // No moderator promotion, no impersonation grant — factory defaults only.
        $this->loginAsUser($portalId, $actor->getUsername(), $actor->getPlainPassword());

        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user={$target->getUsername()}");

        self::assertSame(
            $target->getUsername(),
            $this->authenticatedUsername(),
            'a plain member reaches another member via _switch_user — the impersonation default is opt-out',
        );
    }

    /**
     * Characterization of the escalation on top, NOT an endorsement.
     *
     * UserProvider::loadUserByIdentifier() special-cases the identifier 'root'
     * and returns the server-context root account BEFORE any portal scoping, so
     * the boundary pinned in the test below — the one that stops every other
     * foreign account — does not apply here. Whether ?_switch_user=root works
     * therefore rests entirely on SwitchToUserVoter, which grants every
     * authenticated member.
     *
     * If the voter is tightened, this test must flip to asserting the actor
     * stays themselves.
     */
    public function testPlainMemberCanBecomeRoot(): void
    {
        ['portal' => $portal, 'actor' => $actor] = $this->createScenario();
        $portalId = $portal->getId();

        $this->loginAsUser($portalId, $actor->getUsername(), $actor->getPlainPassword());

        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user=root");

        self::assertSame(
            'root',
            $this->authenticatedUsername(),
            'a plain member reaches the server-context root account via _switch_user',
        );
    }

    /**
     * The one boundary that does hold: UserProvider resolves the target inside
     * the portal from the session, so an account of another portal is not
     * reachable and the actor keeps their own identity.
     */
    public function testSwitchUserCannotReachAnAccountOfAnotherPortal(): void
    {
        ['portal' => $portal, 'actor' => $actor] = $this->createScenario();
        $portalId = $portal->getId();

        $foreignLocal = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $foreignPortal = PortalFactory::createOne(['authSources' => [$foreignLocal]]);
        $foreignAccount = AccountFactory::createOne([
            'portal' => $foreignPortal,
            'authSource' => $foreignLocal,
            'username' => 'foreign.member',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $this->loginAsUser($portalId, $actor->getUsername(), $actor->getPlainPassword());

        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user={$foreignAccount->getUsername()}");

        self::assertSame(
            $actor->getUsername(),
            $this->authenticatedUsername(),
            'the portal-scoped lookup in UserProvider keeps a foreign account out of reach',
        );
    }

    /**
     * @return array{portal: mixed, actor: mixed, target: mixed}
     */
    private function createScenario(): array
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        $actor = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'plain.member',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $target = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'other.member',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        return ['portal' => $portal, 'actor' => $actor, 'target' => $target];
    }

    private function authenticatedUsername(): ?string
    {
        $token = static::getContainer()->get('security.token_storage')->getToken();
        $user = $token?->getUser();

        return $user instanceof Account ? $user->getUsername() : null;
    }
}
