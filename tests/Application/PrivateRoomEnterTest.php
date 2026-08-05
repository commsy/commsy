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
 * A private room is a single user's personal dashboard, so ITEM_ENTER must
 * accept only its owner. RoomController carries
 * #[IsGranted('ITEM_ENTER', subject: 'roomId')] on the class, which means this
 * one rule governs every route of that controller — worth pinning as such.
 *
 * Exercised through the real routes rather than against the voter directly:
 * the owner check reads the account off the token while the surrounding voter
 * still works from the legacy currentUserItem that LegacySubscriber primes per
 * request, and only a real request wires both up the way production does.
 */
final class PrivateRoomEnterTest extends AbstractApplicationTestCase
{
    public function testOwnerCanEnterOwnPrivateRoom(): void
    {
        ['portal' => $portal, 'owner' => $owner] = $this->createScenario();
        $portalId = $portal->getId();

        $this->loginAsUser($portalId, $owner->getUsername(), $owner->getPlainPassword());

        $this->client->request('GET', '/room/'.$this->privateRoomIdOf($owner).'/all');

        self::assertResponseIsSuccessful();
    }

    /**
     * Denial shows up as a redirect, not as 403: App\Security\AccessDeniedHandler
     * bounces a non-XHR request to the requested room's detail page. Asserting
     * that target rather than "not 200" keeps the case from passing on any
     * unrelated redirect.
     */
    public function testStrangerCannotEnterAForeignPrivateRoom(): void
    {
        ['portal' => $portal, 'owner' => $owner, 'stranger' => $stranger] = $this->createScenario();
        $portalId = $portal->getId();
        $privateRoomId = $this->privateRoomIdOf($owner);

        $this->loginAsUser($portalId, $stranger->getUsername(), $stranger->getPlainPassword());

        $this->client->request('GET', "/room/{$privateRoomId}/all");

        self::assertResponseRedirects(
            "/portal/{$portalId}/room/{$privateRoomId}",
            null,
            'a member of the same portal must not reach another account\'s private room',
        );
    }

    /**
     * The guest case is the reason this counts as a leak rather than a mere
     * over-permission: no account at all was needed. Here the firewall's entry
     * point answers, so the bounce goes to the login page.
     */
    public function testGuestCannotEnterAPrivateRoom(): void
    {
        ['portal' => $portal, 'owner' => $owner] = $this->createScenario();

        $this->client->request('GET', '/room/'.$this->privateRoomIdOf($owner).'/all');

        self::assertResponseRedirects('/login/'.$portal->getId());
    }

    /**
     * @return array{portal: mixed, owner: mixed, stranger: mixed}
     */
    private function createScenario(): array
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        // AccountFactory persists through AccountCreatorFacade, which lets the
        // legacy user manager create the account's private room — so this is
        // the real room, not a hand-built one.
        $owner = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'room.owner',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $stranger = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'the.stranger',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        return ['portal' => $portal, 'owner' => $owner, 'stranger' => $stranger];
    }

    /**
     * Resolved over the owning user row instead of the room table alone, so the
     * test cannot accidentally pick up another account's private room.
     */
    private function privateRoomIdOf(Account $account): int
    {
        $roomId = static::getContainer()->get(EntityManagerInterface::class)
            ->getConnection()
            ->fetchOne(
                'SELECT r.item_id FROM room r
                   JOIN user u ON u.context_id = r.item_id
                  WHERE r.type = ? AND u.account_id = ?',
                ['privateroom', $account->getId()]
            );

        self::assertNotFalse($roomId, 'the account has no private room — fixture setup failed');

        return (int) $roomId;
    }
}
