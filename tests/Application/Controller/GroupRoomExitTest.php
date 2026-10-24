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

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The last moderator of a group room leaving the group closes the group room
 * together with its group; every other call deletes nothing.
 */
#[WithStory(AccountStory::class)]
class GroupRoomExitTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $projectId;
    private int $groupId;
    private int $groupRoomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->loginAsUser($this->account->getContextId(), $this->account->getUsername(), $this->account->getPlainPassword());
        $this->projectId = $this->createRoom($this->account->getContextId(), 'Testproject');

        $this->client->request('GET', "/room/$this->projectId/group/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $this->groupId = (int) $this->client->getRequest()->attributes->get('itemId');

        // Saving the draft is what opens the group room.
        $crawler = $this->client->request('GET', "/room/$this->projectId/group/$this->groupId/edit");
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('group[save]')->form();
        $form['group[title]'] = 'Testgroup';
        $this->client->submit($form);
        $this->assertResponseRedirects();

        $this->groupRoomId = $this->groupRoomIdOf($this->groupId);

        // The legacy managers cache what the setup requests loaded; every
        // request after this one starts as in production, on a fresh kernel.
        $this->client->enableReboot();
    }

    public function testLastModeratorLeavingClosesGroupRoomAndGroup(): void
    {
        $this->client->request('GET', "/room/$this->projectId/group/$this->groupId/leave");
        $this->assertResponseRedirects("/room/$this->projectId/group/$this->groupId/leaveanddelete");

        $crawler = $this->client->request('GET', "/room/$this->projectId/group/$this->groupId/leaveanddelete");
        $this->assertResponseIsSuccessful();
        // Asking is not deleting.
        $this->assertGroupRoomAlive();

        $form = $crawler->selectButton('group_room_exit[confirm_button]')->form();
        $form['group_room_exit[confirm_field]'] = $this->confirmString();
        $this->client->submit($form);

        $this->assertResponseRedirects("/room/$this->projectId/group");
        $this->assertTrue($this->isDeleted('room', $this->groupRoomId), 'group room is deleted');
        $this->assertTrue($this->isDeleted('labels', $this->groupId), 'group is deleted');
        $this->assertFalse($this->isDeleted('room', $this->projectId), 'project room is untouched');
    }

    public function testWrongConfirmationDeletesNothing(): void
    {
        $crawler = $this->client->request('GET', "/room/$this->projectId/group/$this->groupId/leaveanddelete");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('group_room_exit[confirm_button]')->form();
        $form['group_room_exit[confirm_field]'] = '';
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertGroupRoomAlive();
    }

    public function testModeratorWhoIsNotTheLastOneLeavesOnlyTheGroup(): void
    {
        $this->addGroupRoomModerator();

        $this->client->request('GET', "/room/$this->projectId/group/$this->groupId/leaveanddelete");
        $this->assertRefused();
        $this->assertGroupRoomAlive();

        $this->client->request('GET', "/room/$this->projectId/group/$this->groupId/leave");
        $this->assertResponseRedirects("/room/$this->projectId/group/$this->groupId");
        $this->assertGroupRoomAlive();
    }

    public function testGroupOfAnotherRoomIsRefused(): void
    {
        $otherProjectId = $this->createRoom($this->account->getContextId(), 'Otherproject');

        $this->client->request('GET', "/room/$otherProjectId/group/$this->groupId/leaveanddelete");
        $this->assertRefused();
        $this->assertGroupRoomAlive();
        $this->assertFalse($this->isDeleted('room', $otherProjectId));
    }

    public function testUnknownGroupIsRefused(): void
    {
        $this->client->request('GET', "/room/$this->projectId/group/".($this->groupId + 100000).'/leaveanddelete');
        $this->assertRefused();
        $this->assertGroupRoomAlive();
    }

    public function testProfileRouteDoesNotClosePastItsOwnName(): void
    {
        $userId = (int) $this->connection()->fetchOne(
            'SELECT item_id FROM user WHERE context_id = :room AND deletion_date IS NULL',
            ['room' => $this->projectId]
        );

        // The former group exit hung off this route as `?groupId`.
        $crawler = $this->client->request(
            'GET',
            "/room/$this->projectId/user/$userId/deleteroomprofile?groupId=$this->groupId&roomEndId=$this->projectId"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('delete_form[confirm_button]')->form();
        $form['delete_form[confirm_field]'] = '';
        $this->client->submit($form, [], ['QUERY_STRING' => "groupId=$this->groupId&roomEndId=$this->projectId"]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertFalse($this->isDeleted('room', $this->projectId), 'project room is untouched');
        $this->assertFalse($this->isDeleted('labels', $this->groupId), 'group is untouched');
        $this->assertGroupRoomAlive();
    }

    /**
     * A refusal bounces to the room page, or ends in 403 where that page is
     * denied too; either way not on the page asked for.
     */
    private function assertRefused(): void
    {
        $response = $this->client->getResponse();

        if ($response->isRedirect()) {
            $this->assertStringStartsWith('/portal/', (string) $response->headers->get('Location'));

            return;
        }

        $this->assertResponseStatusCodeSame(403);
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    private function groupRoomIdOf(int $groupId): int
    {
        $extras = (string) $this->connection()->fetchOne('SELECT extras FROM labels WHERE item_id = :id', ['id' => $groupId]);
        $groupRoomId = (int) (unserialize($extras)['GROUP_ROOM_ID'] ?? 0);
        $this->assertGreaterThan(0, $groupRoomId, 'group has a group room');

        return $groupRoomId;
    }

    private function isDeleted(string $table, int $itemId): bool
    {
        $deletionDate = $this->connection()->fetchOne(
            "SELECT deletion_date FROM $table WHERE item_id = :id",
            ['id' => $itemId]
        );

        return false !== $deletionDate && null !== $deletionDate;
    }

    private function assertGroupRoomAlive(): void
    {
        $this->assertFalse($this->isDeleted('room', $this->groupRoomId), 'group room is alive');
        $this->assertFalse($this->isDeleted('labels', $this->groupId), 'group is alive');
    }

    /** The word to type, in the language of the page last requested. */
    private function confirmString(): string
    {
        return mb_strtoupper(static::getContainer()->get(TranslatorInterface::class)->trans(
            'delete',
            [],
            'profile',
            $this->client->getRequest()->getLocale()
        ));
    }

    private function addGroupRoomModerator(): void
    {
        $groupRoom = static::getContainer()->get(EntityManagerInterface::class)->find(Room::class, $this->groupRoomId);

        RoomUserFactory::new()->asModerator()->create([
            'account' => AccountFactory::createOne([
                'activityState' => Account::ACTIVITY_ACTIVE,
                'locked' => false,
            ]),
            'room' => $groupRoom,
        ]);
    }
}
