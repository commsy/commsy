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
use App\Entity\Room;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The profile pages of a room are a person's own. A moderator may edit other
 * members, but not through these pages: saving them writes the account of
 * whoever is signed in.
 */
#[WithStory(RoomWithMemberStory::class)]
final class ProfilePagesAreOwnTest extends AbstractApplicationTestCase
{
    private Room $room;
    private User $moderator;
    private User $member;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->moderator = RoomWithMemberStory::get('roomUser');
        self::getContainer()->get(Connection::class)->update('user', ['status' => 3], ['item_id' => $this->moderator->getItemId()]);

        $this->member = RoomUserFactory::new()->create([
            'account' => AccountFactory::createOne(['activityState' => Account::ACTIVITY_ACTIVE, 'locked' => false]),
            'room' => $this->room,
        ]);

        $this->loginAsUser((int) $account->getPortal()?->getId(), $account->getUsername(), $account->getPlainPassword());
    }

    public function testAModeratorCannotOpenTheProfilePagesOfAnotherMember(): void
    {
        foreach (['general', 'address', 'contact', 'notifications'] as $page) {
            $this->client->request('GET', "/room/{$this->room->getItemId()}/user/{$this->member->getItemId()}/$page");

            $status = $this->client->getResponse()->getStatusCode();
            self::assertTrue(403 === $status || $this->client->getResponse()->isRedirection(), "$page answered $status");
        }
    }

    public function testSavingTheAddressOfAnotherMemberLeavesBothAccounts(): void
    {
        $db = self::getContainer()->get(Connection::class);
        $profiles = fn (): array => $db->fetchAllKeyValue(
            'SELECT item_id, user_id FROM user WHERE item_id IN (?, ?)',
            [$this->moderator->getItemId(), $this->member->getItemId()],
        );
        $before = $profiles();

        // the form of the own page, with its token, sent to the other member's page
        $crawler = $this->client->request('GET', "/room/{$this->room->getItemId()}/user/{$this->moderator->getItemId()}/address");
        $form = $crawler->selectButton('room_profile_address_save')->form();
        $form['room_profile_address[street]'] = 'Anderswo 1';
        $this->client->request('POST', "/room/{$this->room->getItemId()}/user/{$this->member->getItemId()}/address", $form->getPhpValues());

        self::assertNotSame(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($before, $profiles());
    }

    public function testTheOwnProfilePagesStayOpen(): void
    {
        foreach (['general', 'address', 'contact', 'notifications'] as $page) {
            $this->client->request('GET', "/room/{$this->room->getItemId()}/user/{$this->moderator->getItemId()}/$page");

            self::assertResponseIsSuccessful($page);
        }
    }
}
