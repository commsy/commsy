<?php

namespace App\Room;

use App\Entity\Room;
use App\Utils\ItemService;
use cs_community_item;
use cs_list;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class RoomManager
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    /**
     * AccountManager constructor.
     * @param EntityManagerInterface $entityManager
     * @param ItemService $itemService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ItemService $itemService
    ) {
        $this->entityManager = $entityManager;
        $this->itemService = $itemService;
    }

    /**
     * @param int $roomId
     * @return Room|null
     */
    public function getRoom(int $roomId): ?object
    {
        $roomRepository = $this->entityManager->getRepository(Room::class);
        $room = $roomRepository->findOneBy(['itemId' => $roomId]);
        return $room ?? null;
    }

    /**
     * @param object $room
     * @return cs_list
     */
    public function getLinkedProjectRooms(object $room): cs_list
    {
        if (!$room instanceof Room) {
            throw new LogicException('$room must be of type Room');
        }

        if (!$room->isCommunityRoom()) {
            throw new LogicException('$room must be a community room');
        }

        /** @var cs_community_item $legacyItem */
        $legacyItem = $this->itemService->getTypedItem($room->getItemId());
        if ($legacyItem) {
            return $legacyItem->getProjectList();
        }

        return new cs_list();
    }

    /**
     * @param object $room
     * @param bool $flush
     */
    public function renewActivityUpdated(object $room, bool $flush = true): void
    {
        if (!$room instanceof Room) {
            throw new LogicException('$room must be of type Room');
        }

        $room->setActivityStateUpdated(new DateTime());
        $this->entityManager->persist($room);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param object $room
     * @param bool $resetLastLogin
     * @param bool $resetActivityState
     * @param bool $flush
     */
    public function resetInactivity(
        object $room,
        bool $resetLastLogin = true,
        bool $resetActivityState = true,
        bool $flush = true
    ) : void
    {
        if (!$room instanceof Room) {
            throw new LogicException('$room must be of type Room');
        }

        if ($resetLastLogin) {
            $room->setLastLogin(new DateTime());
        }

        if ($resetActivityState) {
            $room->setActivityState(Room::ACTIVITY_ACTIVE);
            $room->setActivityStateUpdated(null);
        }

        $this->entityManager->persist($room);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     *
     */
    public function resetInactivityToPreviousNonNotificationState(): void
    {
        $roomRepository = $this->entityManager->getRepository(Room::class);

        $roomRepository->updateActivity(Room::ACTIVITY_IDLE_NOTIFIED, Room::ACTIVITY_IDLE);
        $roomRepository->updateActivity(Room::ACTIVITY_ACTIVE_NOTIFIED, Room::ACTIVITY_ACTIVE);
    }
}
