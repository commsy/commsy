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

use App\Room\RoomStatus;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The unlock entry on a group's page lifts a group room lock under the same
 * rules as unlocking any other room.
 */
#[WithStory(AccountStory::class)]
final class GroupRoomUnlockTest extends AbstractApplicationTestCase
{
    public function testTheGroupPageCannotLiftAPortalLockOfTheGroupRoom(): void
    {
        $account = AccountStory::get('account');
        $password = (string) $account->getPlainPassword();
        $portalId = (int) $account->getPortal()->getId();

        $this->loginAsUser($portalId, $account->getUsername(), $password);
        $roomId = $this->createRoom($portalId, 'Projekt');
        $groupId = $this->createGroup($roomId, 'Lerngruppe');
        $groupRoomId = $this->groupRoomId($groupId);
        $this->client->enableReboot();

        $this->logout();
        $this->loginAsRoot();
        $this->lockRoom($portalId, $groupRoomId);
        self::assertSame(RoomStatus::LOCKED_PORTAL_MOD->value, $this->roomStatus($groupRoomId));

        $this->logout();
        $this->loginAsUser($portalId, $account->getUsername(), $password);
        $crawler = $this->client->request('GET', "/room/$roomId/group/$groupId");
        $this->assertResponseIsSuccessful();
        $unlockLink = $crawler->filter('a[href*="unlock"]');
        self::assertCount(1, $unlockLink);
        $this->client->request('GET', (string) $unlockLink->attr('href'));

        self::assertSame(RoomStatus::LOCKED_PORTAL_MOD->value, $this->roomStatus($groupRoomId));
    }

    public function testTheGroupPageLiftsTheModeratorsOwnLock(): void
    {
        $account = AccountStory::get('account');
        $portalId = (int) $account->getPortal()->getId();

        $this->loginAsUser($portalId, $account->getUsername(), (string) $account->getPlainPassword());
        $roomId = $this->createRoom($portalId, 'Projekt');
        $groupId = $this->createGroup($roomId, 'Lerngruppe');
        $groupRoomId = $this->groupRoomId($groupId);
        $this->client->enableReboot();
        $this->lockRoom($roomId, $groupRoomId);
        self::assertSame(RoomStatus::LOCKED->value, $this->roomStatus($groupRoomId));

        $crawler = $this->client->request('GET', "/room/$roomId/group/$groupId");
        $this->client->request('GET', (string) $crawler->filter('a[href*="unlock"]')->attr('href'));

        $this->assertResponseRedirects("/room/$roomId/group/$groupId");
        self::assertSame(RoomStatus::OPEN->value, $this->roomStatus($groupRoomId));
    }

    private function createGroup(int $roomId, string $title): int
    {
        $this->client->request('GET', "/room/$roomId/group/create");
        $this->client->followRedirect();
        $groupId = (int) $this->client->getRequest()->attributes->get('itemId');

        $crawler = $this->client->request('GET', "/room/$roomId/group/$groupId/edit");
        $form = $crawler->selectButton('group[save]')->form();
        $form['group[title]'] = $title;
        $this->client->submit($form);

        $this->client->request('POST', "/room/$roomId/item/$groupId/undraft", [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertResponseIsSuccessful();

        return $groupId;
    }

    private function lockRoom(int $portalId, int $roomId): void
    {
        $crawler = $this->client->request('GET', "/room/$portalId/settings/cancellabledelete/$roomId");
        $form = $crawler->selectButton('cancellable_lock[lock]')->form();
        $locale = $this->client->getResponse()->headers->get('Content-Language') ?? 'en';
        $form['cancellable_lock[confirm]'] = mb_strtoupper(
            static::getContainer()->get(TranslatorInterface::class)->trans('lock', [], 'profile', $locale)
        );
        $this->client->submit($form);
    }

    private function groupRoomId(int $groupId): int
    {
        $extras = unserialize((string) $this->connection()->fetchOne('SELECT extras FROM labels WHERE item_id = ?', [$groupId]));

        return (int) ($extras['GROUP_ROOM_ID'] ?? 0);
    }

    private function roomStatus(int $roomId): int
    {
        return (int) $this->connection()->fetchOne('SELECT status FROM room WHERE item_id = ?', [$roomId]);
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }
}
