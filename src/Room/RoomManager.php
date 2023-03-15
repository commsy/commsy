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

namespace App\Room;

use App\Entity\Room;
use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_community_item;
use cs_environment;
use cs_list;
use cs_room2_manager;
use cs_room_item;
use cs_user_item;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class RoomManager
{
    private cs_environment $legacyEnvironment;

    /**
     * AccountManager constructor.
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ItemService $itemService,
        private CalendarsService $calendarsService,
        LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @return Room|null
     */
    public function getRoom(int $roomId): ?object
    {
        $roomRepository = $this->entityManager->getRepository(Room::class);
        $room = $roomRepository->findOneBy(['itemId' => $roomId]);

        return $room ?? null;
    }

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

    public function resetInactivity(
        object $room,
        bool $resetLastLogin = true,
        bool $resetActivityState = true,
        bool $flush = true
    ): void {
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

    public function resetInactivityToPreviousNonNotificationState(): void
    {
        $roomRepository = $this->entityManager->getRepository(Room::class);

        $roomRepository->updateActivity(Room::ACTIVITY_IDLE_NOTIFIED, Room::ACTIVITY_IDLE);
        $roomRepository->updateActivity(Room::ACTIVITY_ACTIVE_NOTIFIED, Room::ACTIVITY_ACTIVE);
    }

    /**
     * Returns a new room with the given properties, created by the given room manager.
     *
     * @param cs_room2_manager $roomManager the room manager to be used to create the room (which also defines its type)
     * @param int               $contextID   the ID of the room which hosts the created room
     * @param string            $title       the title of the created room
     * @param string            $description (optional) the description of the created room
     * @param cs_room_item|null (optional) $roomTemplate the room to be used as a template when creating the new room
     * @param cs_user_item|null (optional) $creator the user who will be specified as the room's creator; if left out,
     * the current user will be used
     * @param cs_user_item|null (optional) $modifier the user who will be specified as the room's modifier; if left out,
     * the current user will be used
     *
     * @return cs_room_item|null the newly created room, or null if an error occurred
     */
    public function createRoom(
        cs_room2_manager $roomManager,
        int $contextID,
        string $title,
        string $description = '',
        cs_room_item $roomTemplate = null,
        cs_user_item $creator = null,
        cs_user_item $modifier = null
    ): ?cs_room_item {
        // TODO: use a facade/factory to create a new room

        if (!isset($roomManager) || empty($contextID) || empty($title)) {
            return null;
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $creator ??= $currentUser;
        $modifier ??= $currentUser;

        $newRoom = $roomManager->getNewItem();
        if (!$newRoom) {
            return null;
        }

        $newRoom->setCreatorItem($creator);
        $newRoom->setModificatorItem($modifier);
        $newRoom->setCreationDate(date('Y-m-d H:i:s'));

        $newRoom->setContextID($contextID);
        $newRoom->open();

        $newRoom->setTitle($title);
        $newRoom->setDescription($description);

        // TODO: in case of a project room, assign the community rooms to which this room belongs (from a method parameter)
        // TODO: set the room's time intervals (from a method parameter)

        // persist room (which will also call $roomManager->saveItem())
        $newRoom->save();

        $this->calendarsService->createCalendar($newRoom, null, null, true);

        if ($roomTemplate) {
            $newRoom = $this->copySettings($roomTemplate, $newRoom);
        }

        // TODO: set the room's system language (from a method parameter)

        // mark the room as edited
        $linkModifierItemManager = $this->legacyEnvironment->getLinkModifierItemManager();
        $linkModifierItemManager->markEdited($newRoom->getItemID(), $modifier->getItemID());

        // TODO: set any room categories (from a method parameter)

        return $newRoom;
    }
}
