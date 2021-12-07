<?php

namespace App\Room;

use App\Entity\Room;
use App\Entity\ZzzRoom;
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
     * AccountManager constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $roomId
     * @return Room|ZzzRoom|null
     */
    public function getRoom(int $roomId): ?object
    {
        $roomRepository = $this->entityManager->getRepository(Room::class);
        $room = $roomRepository->findOneBy(['itemId' => $roomId]);
        if ($room) {
            return $room;
        }

        $zzzRoomRepository = $this->entityManager->getRepository(ZzzRoom::class);
        $room = $zzzRoomRepository->findOneBy(['itemId' => $roomId]);
        if ($room) {
            return $room;
        }

        return null;
    }

    /**
     * @param object $room
     * @param bool $flush
     */
    public function renewActivityUpdated(object $room, bool $flush = true): void
    {
        if (!$room instanceof Room && !$room instanceof ZzzRoom) {
            throw new LogicException('$room must be of type Room or ZzzRoom');
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
        if (!$room instanceof Room && !$room instanceof ZzzRoom) {
            throw new LogicException('$room must be of type Room or ZzzRoom');
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
        $zzzRoomRepository = $this->entityManager->getRepository(ZzzRoom::class);

        $roomRepository->updateActivity(Room::ACTIVITY_IDLE_NOTIFIED, Room::ACTIVITY_IDLE);
        $roomRepository->updateActivity(Room::ACTIVITY_ACTIVE_NOTIFIED, Room::ACTIVITY_ACTIVE);

        $zzzRoomRepository->updateActivity(Room::ACTIVITY_IDLE_NOTIFIED, Room::ACTIVITY_IDLE);
        $zzzRoomRepository->updateActivity(Room::ACTIVITY_ACTIVE_NOTIFIED, Room::ACTIVITY_ACTIVE);
    }
}