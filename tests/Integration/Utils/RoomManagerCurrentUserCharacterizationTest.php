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
use App\Room\RoomManager;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_room_item;
use cs_user_item;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Phase 3.5 — characterization of RoomManager::createRoom, the sole
 * getCurrentUserItem callsite in RoomManager (line 154).
 *
 * Pinned behaviour: when createRoom is called without an explicit
 * creator/modifier, the new room's creator and modifier default to the
 * legacy current user; an explicitly passed creator wins. Schritt 4
 * reroutes the `$creator ??= $currentUser` default onto
 * CurrentUserResolver — these assertions must stay green.
 *
 * Reuse-priority suite: RoomManager is a caller in lock 1 and 2.
 */
#[WithStory(AccountStory::class)]
final class RoomManagerCurrentUserCharacterizationTest extends KernelTestCase
{
    private RoomManager $roomManager;
    private UserService $userService;
    private cs_environment $legacyEnvironment;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->roomManager = self::getContainer()->get(RoomManager::class);
        $this->userService = self::getContainer()->get(UserService::class);
        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->account = AccountStory::get('account');
    }

    public function testCreatedRoomDefaultsCreatorAndModifierToCurrentUser(): void
    {
        $currentUser = $this->primeCurrentUser();

        $room = $this->roomManager->createRoom(
            $this->legacyEnvironment->getProjectManager(),
            $this->portalId(),
            $this->portalId(),
            'Characterization room (default creator)',
        );

        self::assertInstanceOf(cs_room_item::class, $room);
        self::assertSame($currentUser->getItemID(), $room->getCreatorItem()->getItemID());
        self::assertSame($currentUser->getItemID(), $room->getModificatorItem()->getItemID());
    }

    public function testExplicitCreatorOverridesCurrentUserDefault(): void
    {
        $this->primeCurrentUser();
        $explicitCreator = $this->userService->getUserInContext(
            $this->secondAccountWithMembership(),
            $this->portalId(),
        );
        self::assertInstanceOf(cs_user_item::class, $explicitCreator);

        $room = $this->roomManager->createRoom(
            $this->legacyEnvironment->getProjectManager(),
            $this->portalId(),
            $this->portalId(),
            'Characterization room (explicit creator)',
            '',
            null,
            $explicitCreator,
        );

        self::assertInstanceOf(cs_room_item::class, $room);
        self::assertSame($explicitCreator->getItemID(), $room->getCreatorItem()->getItemID());
    }

    // ---- helpers

    private function primeCurrentUser(): cs_user_item
    {
        RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => RoomFactory::new()->project()->create([
                'contextId' => $this->portalId(),
                'portal' => $this->account->getPortal(),
            ]),
            'status' => 2,
        ]);

        $userItem = $this->userService->getUserInContext($this->account, $this->portalId());
        self::assertInstanceOf(cs_user_item::class, $userItem);
        $this->legacyEnvironment->setCurrentUserItem($userItem);

        return $userItem;
    }

    private function secondAccountWithMembership(): Account
    {
        $portal = $this->account->getPortal();
        $second = \Tests\Factory\AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal?->getAuthSources()->first(),
        ]);
        RoomUserFactory::createOne([
            'account' => $second,
            'room' => RoomFactory::new()->project()->create([
                'contextId' => $this->portalId(),
                'portal' => $portal,
            ]),
            'status' => 2,
        ]);

        return $second;
    }

    private function portalId(): int
    {
        $id = $this->account->getPortal()?->getId();
        self::assertNotNull($id);

        return $id;
    }
}
