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
use App\Entity\User;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(RoomWithMemberStory::class)]
class UserControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private Room $room;
    private User $roomUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
    }

    public function testListRenders(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/user");
        $this->assertResponseIsSuccessful();
    }

    public function testGridViewRenders(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/user/gridView");
        $this->assertResponseIsSuccessful();
    }

    public function testFeedRenders(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/user/feed/0/date");
        $this->assertResponseIsSuccessful();
    }

    public function testGridFeedRenders(): void
    {
        $this->client->request('GET', "/room/{$this->room->getItemId()}/user/grid/0/date");
        $this->assertResponseIsSuccessful();
    }

    public function testDetailRenders(): void
    {
        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/user/{$this->roomUser->getItemId()}"
        );
        $this->assertResponseIsSuccessful();
    }

    public function testInitialsRenders(): void
    {
        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/user/{$this->roomUser->getItemId()}/initials"
        );
        $this->assertResponseIsSuccessful();
    }

    public function testSendFormRenders(): void
    {
        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/user/{$this->roomUser->getItemId()}/send"
        );
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testGuestImageRenders(): void
    {
        // A static placeholder graphic with no personal reference — public on
        // purpose, and the one route here that carries no permission check.
        $this->client->request('GET', '/room/user/guestimage');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Every write and mail route needs the caller established before it runs.
     *
     * @param array<string, string> $payload
     */
    #[DataProvider('guardedRoutes')]
    public function testRouteRequiresAuthentication(string $method, string $path, array $payload = []): void
    {
        $this->logout();

        $this->client->request(
            $method,
            str_replace(
                ['{roomId}', '{itemId}'],
                [(string) $this->room->getItemId(), (string) $this->roomUser->getItemId()],
                $path
            ),
            $payload,
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $status = $this->client->getResponse()->getStatusCode();
        self::assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden for {$path}, got {$status}"
        );
    }

    public static function guardedRoutes(): iterable
    {
        $select = ['action' => 'pin', 'positiveItemIds' => ['1']];

        yield 'xhr pin' => ['POST', '/room/{roomId}/user/xhr/pin', $select];
        yield 'xhr unpin' => ['POST', '/room/{roomId}/user/xhr/unpin', $select];
        yield 'xhr markread' => ['POST', '/room/{roomId}/user/xhr/markread', $select];
        yield 'insert userroom' => ['POST', '/room/{roomId}/user/insertUserroom', $select];
        yield 'send mail' => ['POST', '/room/{roomId}/user/sendmail', []];
        yield 'send multiple' => ['POST', '/room/{roomId}/user/sendMultiple', []];
        yield 'send multiple success' => ['GET', '/room/{roomId}/user/sendMultiple/success', []];
        yield 'send success' => ['GET', '/room/{roomId}/user/{itemId}/send/success', []];
        yield 'contact form' => ['GET', '/room/{roomId}/user/{itemId}/contactForm/app_room_home', []];
        yield 'contact success' => ['GET', '/room/{roomId}/user/{itemId}/send/success/contact/app_room_home', []];
    }

    /**
     * Pinning is a write, so the assertion is on the stored state rather than
     * on the status code.
     */
    public function testAnonymousPinLeavesTheStoredStateUnchanged(): void
    {
        $connection = self::getContainer()->get(Connection::class);
        $itemId = $this->roomUser->getItemId();

        $before = $connection->fetchOne('SELECT pinned FROM items WHERE item_id = ?', [$itemId]);

        $this->logout();
        $this->client->request(
            'POST',
            "/room/{$this->room->getItemId()}/user/xhr/pin",
            ['action' => 'pin', 'positiveItemIds' => [(string) $itemId]],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        self::assertSame(
            $before,
            $connection->fetchOne('SELECT pinned FROM items WHERE item_id = ?', [$itemId])
        );
    }

    /**
     * Pasting the clipboard into other members' user rooms is offered to
     * moderators only, so the route asks for the same.
     */
    public function testInsertUserroomRequiresModerator(): void
    {
        $outsiderPassword = 'outsider-secret';
        $outsider = AccountFactory::createOne([
            'portal' => $this->account->getPortal(),
            'authSource' => $this->account->getAuthSource(),
            'plainPassword' => $outsiderPassword,
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $this->logout();
        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $outsider->getUsername(),
            $outsiderPassword
        );

        $this->client->request(
            'POST',
            "/room/{$this->room->getItemId()}/user/insertUserroom",
            ['action' => 'insertuserroom', 'positiveItemIds' => ['1']],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $status = $this->client->getResponse()->getStatusCode();
        self::assertTrue(
            $status === 302 || $status === 403,
            "expected redirect or forbidden, got {$status}"
        );
    }
}
