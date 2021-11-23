<?php

namespace App\Room;

use App\Entity\Account;
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
     */
    public function resetInactivity(
        object $room,
        bool $resetLastLogin = true,
        bool $resetActivityState = true
    ) : void
    {
        if (!$room instanceof Room && !$room instanceof ZzzRoom) {
            throw new LogicException('$room must be of type Room or ZzzRoom');
        }

        if ($resetLastLogin) {
            $room->setLastLogin(new DateTime());
        }

        if ($resetActivityState) {
            $room->setActivityState(Account::ACTIVITY_ACTIVE);
            $room->setActivityStateUpdated(null);
        }

        $this->entityManager->persist($room);
        $this->entityManager->flush();
    }
}