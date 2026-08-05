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
 * Access-control tests for Symfony's switch_user listener, which is enabled on
 * the main firewall with `role: CAN_SWITCH_USER`. The rule therefore has to
 * hold for every route under that firewall, not only for the portal settings
 * take-over route, which is why these tests exercise it away from that route.
 *
 * They answer the question a voter test cannot: does the listener actually
 * change the authenticated identity end to end?
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

        $this->promoteToPortalModerator($actor);
        $this->loginAsUser($portalId, $actor->getUsername(), $actor->getPlainPassword());

        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user={$target->getUsername()}");

        self::assertSame(
            $target->getUsername(),
            $this->authenticatedUsername(),
            'the _switch_user parameter is honoured outside the portal settings route',
        );
    }

    /**
     * Taking an account over requires portal moderator status.
     *
     * `cs_user_item::getCanImpersonateAnotherUser()` is an opt-OUT
     * (`!_issetExtra('DEACTIVATE_LOGIN_AS')`) and returns true for a fresh
     * account, so it cannot carry this decision on its own — the moderator
     * requirement is what does.
     */
    public function testPlainMemberCannotImpersonateAnotherMember(): void
    {
        ['portal' => $portal, 'actor' => $actor, 'target' => $target] = $this->createScenario();
        $portalId = $portal->getId();

        // No moderator promotion — factory defaults only.
        $this->loginAsUser($portalId, $actor->getUsername(), $actor->getPlainPassword());

        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user={$target->getUsername()}");

        self::assertSame(
            $actor->getUsername(),
            $this->authenticatedUsername(),
            'a plain member must not be able to assume another identity',
        );
    }

    /**
     * Why the target's portal carries the rule rather than the actor's status
     * alone.
     *
     * UserProvider::loadUserByIdentifier() special-cases the identifier 'root'
     * and returns the server-context root account BEFORE any portal scoping, so
     * the boundary pinned further below — the one that covers every other
     * account — does not reach it. The decision rests entirely on
     * SwitchToUserVoter, hence this case is pinned on its own.
     */
    public function testPlainMemberCannotBecomeRoot(): void
    {
        ['portal' => $portal, 'actor' => $actor] = $this->createScenario();
        $portalId = $portal->getId();

        $this->loginAsUser($portalId, $actor->getUsername(), $actor->getPlainPassword());

        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user=root");

        self::assertSame(
            $actor->getUsername(),
            $this->authenticatedUsername(),
            'a plain member must not be able to become root',
        );
    }

    /**
     * Not even a portal moderator may become root: the root account has no
     * portal, so there is no portal to be a moderator of.
     */
    public function testPortalModeratorCannotBecomeRoot(): void
    {
        ['portal' => $portal, 'actor' => $actor] = $this->createScenario();
        $portalId = $portal->getId();

        $this->promoteToPortalModerator($actor);
        $this->loginAsUser($portalId, $actor->getUsername(), $actor->getPlainPassword());

        $this->client->request('GET', "/portal/{$portalId}/enter?_switch_user=root");

        self::assertSame(
            $actor->getUsername(),
            $this->authenticatedUsername(),
            'portal moderation does not reach the server-context root account',
        );
    }

    /**
     * The portal boundary, now enforced by the voter as well. It held before
     * this change too, but only further downstream: UserProvider resolves the
     * target inside the portal from the session, so a foreign account was never
     * reachable — a coincidence rather than a guard. The actor is a moderator
     * here so the case still tests the boundary and not the new moderator
     * requirement.
     */
    public function testSwitchUserCannotReachAnAccountOfAnotherPortal(): void
    {
        ['portal' => $portal, 'actor' => $actor] = $this->createScenario();
        $portalId = $portal->getId();

        $this->promoteToPortalModerator($actor);

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

    /**
     * Raises the account's portal-level user row to moderator. Done before the
     * account authenticates, so no stale cs_user_item is cached.
     */
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
