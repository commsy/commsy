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

namespace Tests\Integration\Utils;

use App\Entity\Account;
use App\Entity\Room;
use App\Mail\Helper\EmailSendStatus;
use App\Mail\Mailer;
use App\Services\LegacyEnvironment;
use App\Utils\AccountMail;
use App\Utils\UserService;
use cs_environment;
use cs_user_item;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterizes UserService::sendUserInfoMail()'s "from sender" name.
 *
 * The from-name is meant to be the portal that owns the current
 * context. For a room context the code climbs room->getContextItem()
 * (== the owning portal). When the current context is itself a
 * portal/server, getCurrentContextItem() returns a PortalProxy, which
 * has no getContextItem() — the old unguarded
 * `getCurrentContextItem()->getContextItem()` therefore fataled with
 * "Call to undefined method PortalProxy::getContextItem()" in
 * portal/server contexts (e.g. account-creation flows via
 * UserCreatorFacade).
 *
 * Intended semantics: in a room the from-name is the room's own title
 * (consistent with the mail subject/body); otherwise the portal title;
 * otherwise 'CommSy'.
 *
 * - Room context: from-name == the room title (the old double-climb
 *   wrongly used the portal title here).
 * - Portal context: no fatal, from-name == portal title (the fix).
 */
#[WithStory(AccountStory::class)]
final class UserServiceSendUserInfoMailContextCharacterizationTest extends KernelTestCase
{
    private UserService $userService;
    private cs_environment $legacyEnvironment;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->userService = self::getContainer()->get(UserService::class);
        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->account = AccountStory::get('account');
    }

    public function testRoomContextFromSenderIsRoomTitle(): void
    {
        $room = $this->createRoom();
        $userItemId = $this->memberUserItemId($room);

        $this->legacyEnvironment->setCurrentContextID($room->getItemId());

        $legacyRoomTitle = $this->legacyEnvironment->getRoomManager()
            ->getItem($room->getItemId())->getTitle();

        $captured = $this->captureFromSender($userItemId);

        self::assertSame($legacyRoomTitle, $captured);
        self::assertNotSame(
            $this->account->getPortal()?->getTitle(),
            $captured,
            'in a room the from-name must be the room title, not the portal title',
        );
    }

    public function testPortalContextDoesNotFatalAndUsesPortalTitle(): void
    {
        $room = $this->createRoom();
        $userItemId = $this->memberUserItemId($room);

        // Current context IS the portal -> getCurrentContextItem() yields
        // a PortalProxy. Pre-fix this fataled on ->getContextItem().
        $portalId = $this->account->getPortal()?->getId();
        $this->legacyEnvironment->setCurrentContextID($portalId);

        $captured = $this->captureFromSender($userItemId);

        self::assertSame($this->account->getPortal()?->getTitle(), $captured);
    }

    // ---- helpers

    private function captureFromSender(int $userItemId): ?string
    {
        $captured = null;

        $mailer = $this->createMock(Mailer::class);
        $mailer->method('sendRaw')->willReturnCallback(
            function (string $subject, string $body, $recipient, string $fromSenderName = 'CommSy') use (&$captured): EmailSendStatus {
                $captured = $fromSenderName;

                return new EmailSendStatus(true, 1, [], []);
            }
        );

        $accountMail = $this->createMock(AccountMail::class);
        $accountMail->method('generateSubject')->willReturn('subject');
        $accountMail->method('generateBody')->willReturn('body');

        $this->userService->sendUserInfoMail($mailer, $accountMail, [$userItemId], 'user-status-moderator');

        return $captured;
    }

    private function memberUserItemId(Room $room): int
    {
        RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $room,
            'status' => 3,
        ]);

        $userItem = $this->userService->getUserInContext($this->account, $room->getItemId());
        self::assertInstanceOf(cs_user_item::class, $userItem);

        return $userItem->getItemID();
    }

    private function createRoom(): Room
    {
        return RoomFactory::new()->project()->create([
            'title' => 'Sender Char Room '.uniqid(),
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
    }
}
