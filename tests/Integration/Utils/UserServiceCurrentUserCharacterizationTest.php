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
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_room_item;
use cs_user_item;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Phase 3.5 — characterization of the UserService behaviour reachable
 * through its `getCurrentUserItem()` dependency (callsites at lines
 * 381, 581, 608, 639, 695). These are the moderator-check methods that
 * default the actor to the legacy current user when no explicit user
 * is passed.
 *
 * This is a reuse-priority suite: the same four methods are touched
 * again by lock 2 (context) and lock 3 (per-itemtype). Pinning them
 * once protects all three migrations. When Schritt 4 reroutes the
 * `$user ??= legacyEnvironment->getCurrentUserItem()` default onto the
 * CurrentUserResolver, every assertion here must stay green.
 *
 * Scope is deliberately the negative/positive branches that are clean
 * to drive from factories without mutating legacy caches — that keeps
 * the characterization deterministic and faithful.
 */
#[WithStory(AccountStory::class)]
final class UserServiceCurrentUserCharacterizationTest extends KernelTestCase
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

    // ---- userIsModeratorForRoom (defaults to current user)

    public function testCurrentUserModeratorOfRoomIsRecognised(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room, status: 3);
        $this->loginAsCurrentUser($room);

        self::assertTrue(
            $this->userService->userIsModeratorForRoom($this->legacyRoom($room)),
        );
    }

    public function testCurrentUserPlainMemberIsNotModerator(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room, status: 2);
        $this->loginAsCurrentUser($room);

        self::assertFalse(
            $this->userService->userIsModeratorForRoom($this->legacyRoom($room)),
        );
    }

    public function testExplicitUserArgumentOverridesCurrentUserDefault(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room, status: 3);
        $this->loginAsCurrentUser($room);

        // Explicit guest-ish other user (no membership) must NOT inherit
        // the current moderator's verdict.
        $other = new cs_user_item($this->legacyEnvironment);
        $other->setStatus(0);
        $other->setUserID('someone-else');

        self::assertFalse(
            $this->userService->userIsModeratorForRoom($this->legacyRoom($room), $other),
        );
    }

    // ---- userIsLastModeratorForRoom (defaults to current user)

    public function testNullRoomIsNeverLastModerator(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room, status: 3);
        $this->loginAsCurrentUser($room);

        self::assertFalse(
            $this->userService->userIsLastModeratorForRoom(null),
        );
    }

    public function testSoleModeratorIsLastModerator(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room, status: 3);
        $this->loginAsCurrentUser($room);

        self::assertTrue(
            $this->userService->userIsLastModeratorForRoom($this->legacyRoom($room)),
        );
    }

    public function testNotLastModeratorWhenASecondModeratorExists(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room, status: 3);
        $this->createMember($this->secondAccount(), $room, status: 3);
        $this->loginAsCurrentUser($room);

        self::assertFalse(
            $this->userService->userIsLastModeratorForRoom($this->legacyRoom($room)),
        );
    }

    // ---- userIsParentModeratorForRoom / userIsPortalModerator
    //      (default-to-current-user wiring + the false path)

    public function testPlainMemberIsNotParentModerator(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room, status: 2);
        $this->loginAsCurrentUser($room);

        self::assertFalse(
            $this->userService->userIsParentModeratorForRoom($this->legacyRoom($room)),
        );
    }

    public function testPlainMemberIsNotPortalModerator(): void
    {
        $room = $this->createRoom();
        $this->createMember($this->account, $room, status: 2);
        $this->loginAsCurrentUser($room);

        self::assertFalse(
            $this->userService->userIsPortalModerator(),
        );
    }

    // ---- helpers

    /**
     * Mirrors what LegacySubscriber effectively leaves behind for this
     * (account, room): the security token plus the legacy current user
     * item primed to the account's membership in that room.
     */
    private function loginAsCurrentUser(Room $room): void
    {
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($this->account, 'main', $this->account->getRoles()),
        );

        $userItem = $this->userService->getUserInContext($this->account, $room->getItemId());
        self::assertInstanceOf(cs_user_item::class, $userItem);
        $this->legacyEnvironment->setCurrentUserItem($userItem);
    }

    private function legacyRoom(Room $room): cs_room_item
    {
        $legacy = $this->legacyEnvironment->getRoomManager()->getItem($room->getItemId());
        self::assertInstanceOf(cs_room_item::class, $legacy);

        return $legacy;
    }

    private function createRoom(): Room
    {
        return RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
    }

    private function createMember(Account $account, Room $room, int $status): void
    {
        RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
            'status' => $status,
        ]);
    }

    private function secondAccount(): Account
    {
        $portal = $this->account->getPortal();

        return \Tests\Factory\AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal?->getAuthSources()->first(),
        ]);
    }
}
