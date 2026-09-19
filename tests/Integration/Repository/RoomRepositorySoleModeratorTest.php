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

namespace Tests\Integration\Repository;

use App\Entity\Account;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Room\RoomType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Repository contract for {@see RoomRepository::findRoomsWithSoleModerator()}:
 * rooms of the requested types in the account's portal in which this account
 * is the only living moderator.
 *
 * This is the protection that keeps the inactivity workflow from
 * deprovisioning the last moderator of a room. The room types are an
 * argument on purpose — the legacy room list widened to group rooms
 * whenever a current portal happened to be set in the process, which made
 * the answer depend on what the worker had handled before.
 */
#[WithStory(AccountStory::class)]
final class RoomRepositorySoleModeratorTest extends KernelTestCase
{
    private const array MAIN_ROOMS = [RoomType::Project->value, RoomType::Community->value];
    private const array WITH_GROUP_ROOMS = [
        RoomType::Project->value,
        RoomType::Community->value,
        RoomType::GroupRoom->value,
    ];

    private RoomRepository $roomRepository;
    private Account $account;

    public function testFindsRoomWhereAccountIsTheOnlyModerator(): void
    {
        $room = $this->createRoom();
        $this->makeModerator($room, $this->account);

        self::assertSame(
            [$room->getItemId()],
            $this->itemIds($this->roomRepository->findRoomsWithSoleModerator($this->account, self::MAIN_ROOMS)),
        );
    }

    public function testIgnoresRoomThatHasASecondModerator(): void
    {
        $room = $this->createRoom();
        $this->makeModerator($room, $this->account);
        $this->makeModerator($room, $this->createSecondAccount());

        self::assertSame([], $this->roomRepository->findRoomsWithSoleModerator($this->account, self::MAIN_ROOMS));
    }

    public function testIgnoresRoomWhereAccountIsAPlainMember(): void
    {
        $room = $this->createRoom();
        $this->makeModerator($room, $this->createSecondAccount());
        RoomUserFactory::new()->asUser()->create(['account' => $this->account, 'room' => $room]);

        self::assertSame([], $this->roomRepository->findRoomsWithSoleModerator($this->account, self::MAIN_ROOMS));
    }

    public function testFindsGroupRoomOnlyWhenThatTypeIsRequested(): void
    {
        $groupRoom = $this->createRoom(RoomType::GroupRoom);
        $this->makeModerator($groupRoom, $this->account);

        self::assertSame(
            [],
            $this->roomRepository->findRoomsWithSoleModerator($this->account, self::MAIN_ROOMS),
            'Group rooms must only appear when the caller asks for them',
        );
        self::assertSame(
            [$groupRoom->getItemId()],
            $this->itemIds($this->roomRepository->findRoomsWithSoleModerator($this->account, self::WITH_GROUP_ROOMS)),
        );
    }

    public function testIncludesArchivedRooms(): void
    {
        $room = RoomFactory::new()->project()->archived()->create([
            'contextId' => $this->portalId(),
            'portal' => $this->account->getPortal(),
        ]);
        $this->makeModerator($room, $this->account);

        self::assertSame(
            [$room->getItemId()],
            $this->itemIds($this->roomRepository->findRoomsWithSoleModerator($this->account, self::MAIN_ROOMS)),
            'The legacy list did not filter on archived, and archiving does not hand the room to anyone else',
        );
    }

    public function testIgnoresSoftDeletedRooms(): void
    {
        $room = RoomFactory::new()->project()->deleted()->create([
            'contextId' => $this->portalId(),
            'portal' => $this->account->getPortal(),
        ]);
        $this->makeModerator($room, $this->account);

        self::assertSame([], $this->roomRepository->findRoomsWithSoleModerator($this->account, self::MAIN_ROOMS));
    }

    public function testSoftDeletedModeratorsDoNotCount(): void
    {
        $room = $this->createRoom();
        $this->makeModerator($room, $this->account);
        RoomUserFactory::new()->softDeleted()->create([
            'account' => $this->createSecondAccount(),
            'room' => $room,
            'status' => 3,
        ]);

        self::assertSame(
            [$room->getItemId()],
            $this->itemIds($this->roomRepository->findRoomsWithSoleModerator($this->account, self::MAIN_ROOMS)),
            'A ghost moderator must not make the room look jointly moderated',
        );
    }

    public function testIgnoresOwnSoftDeletedMembership(): void
    {
        $room = $this->createRoom();
        RoomUserFactory::new()->softDeleted()->create([
            'account' => $this->account,
            'room' => $room,
            'status' => 3,
        ]);

        self::assertSame([], $this->roomRepository->findRoomsWithSoleModerator($this->account, self::MAIN_ROOMS));
    }

    public function testIgnoresRoomsOutsideTheAccountsPortal(): void
    {
        $room = RoomFactory::new()->project()->create([
            'contextId' => $this->portalId() + 1,
            'portal' => $this->account->getPortal(),
        ]);
        $this->makeModerator($room, $this->account);

        self::assertSame([], $this->roomRepository->findRoomsWithSoleModerator($this->account, self::MAIN_ROOMS));
    }

    public function testReturnsNothingForAnEmptyTypeList(): void
    {
        $room = $this->createRoom();
        $this->makeModerator($room, $this->account);

        self::assertSame([], $this->roomRepository->findRoomsWithSoleModerator($this->account, []));
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->roomRepository = self::getContainer()->get(RoomRepository::class);
        $this->account = AccountStory::get('account');
    }

    private function createRoom(RoomType $type = RoomType::Project): Room
    {
        return RoomFactory::new()->with(['type' => $type->value])->create([
            'contextId' => $this->portalId(),
            'portal' => $this->account->getPortal(),
        ]);
    }

    private function makeModerator(Room $room, Account $account): void
    {
        RoomUserFactory::new()->asModerator()->create([
            'account' => $account,
            'room' => $room,
        ]);
    }

    private function createSecondAccount(): Account
    {
        $portal = $this->account->getPortal();

        return AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal?->getAuthSources()->first(),
        ]);
    }

    private function portalId(): int
    {
        return $this->account->getPortal()->getId();
    }

    /**
     * @param Room[] $rooms
     *
     * @return int[]
     */
    private function itemIds(array $rooms): array
    {
        return array_map(static fn (Room $room): int => $room->getItemId(), $rooms);
    }
}
