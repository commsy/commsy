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
use App\Room\RoomStatus;
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The lock notice on the room page names what holds the room locked. A room
 * locked by the deprovisioning is about to be deleted, which the notice says.
 */
#[WithStory(AccountStory::class)]
final class RoomLockNoticeTest extends AbstractApplicationTestCase
{
    private int $portalId;
    private int $roomId;
    private Connection $db;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Account $account */
        $account = AccountStory::get('account');
        $this->portalId = $account->getContextId();
        $this->db = self::getContainer()->get(Connection::class);
        // the account language is random; the assertions read the English page
        $this->db->update('accounts', ['language' => 'en'], ['id' => $account->getId()]);
        $this->loginAsUser($this->portalId, $account->getUsername(), $account->getPlainPassword());
        $this->roomId = $this->createRoom($this->portalId, 'Testraum');
    }

    public function testALockByTheDeprovisioningIsNamedAndAnnouncesTheDeletion(): void
    {
        $html = $this->showRoom(RoomStatus::LOCKED, Room::ACTIVITY_IDLE);

        self::assertStringContainsString('Locked due to inactivity', $html);
        self::assertStringContainsString('it will be deleted due to inactivity', $html);
    }

    public function testALockByThePortalModerationKeepsItsNameAndAnnouncesTheDeletion(): void
    {
        $html = $this->showRoom(RoomStatus::LOCKED_PORTAL_MOD, Room::ACTIVITY_IDLE_NOTIFIED);

        self::assertStringContainsString('Locked by portal moderation', $html);
        self::assertStringContainsString('it will be deleted due to inactivity', $html);
    }

    public function testAnotherLockClaimsNoCauseAndNoDeletion(): void
    {
        $html = $this->showRoom(RoomStatus::LOCKED, Room::ACTIVITY_ACTIVE);

        self::assertMatchesRegularExpression('~<p>\s*Locked\s*</p>~', $html);
        self::assertStringNotContainsString('inactivity', $html);
    }

    public function testTheGermanNoticeNamesTheInactivity(): void
    {
        $translator = self::getContainer()->get(TranslatorInterface::class);

        self::assertSame('Gesperrt wegen Inaktivität', $translator->trans('room.status.description', ['room_status' => 'LOCKED_INACTIVE'], 'room', 'de'));
        self::assertSame('Gesperrt', $translator->trans('room.status.description', ['room_status' => 'LOCKED'], 'room', 'de'));
        self::assertSame(
            'Wird er nicht entsperrt, wird er wegen Inaktivität gelöscht.',
            $translator->trans('room.locked_for_inactivity.info', [], 'room', 'de'),
        );
    }

    private function showRoom(RoomStatus $status, string $activityState): string
    {
        $this->db->update('room', [
            'status' => $status->value,
            'activity_state' => $activityState,
        ], ['item_id' => $this->roomId]);

        $this->client->request('GET', "/portal/{$this->portalId}/room/{$this->roomId}");
        self::assertResponseIsSuccessful();

        return (string) $this->client->getResponse()->getContent();
    }
}
