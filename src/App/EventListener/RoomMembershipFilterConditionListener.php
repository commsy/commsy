<?php

namespace App\EventListener;

use Lexik\Bundle\FormFilterBundle\Event\GetFilterConditionEvent;

use Commsy\LegacyBundle\Utils\UserService;

/**
 * EventListener for use with LexikFormFilterBundle, which customizes
 * the doctrine conditions for the room membership filter
 */
class RoomMembershipFilterConditionListener
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Limits the room results to those the current user is member of.
     * 
     * @param  GetFilterConditionEvent $event the event
     */
    public function onGetFilterCondition(GetFilterConditionEvent $event)
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if (!empty($values['value'])) {

            $currentUserItem = $this->userService->getCurrentUserItem();
            $userRoomList = $this->userService->getRoomList($currentUserItem);
            $userArchivedRoomList = $this->userService->getArchivedRoomList($currentUserItem);
            $userRoomList = array_merge($userRoomList, $userArchivedRoomList);

            $roomIds = [];
            foreach ($userRoomList as $room) {
                $roomIds[] = $room->getItemId();
            }

            if (!empty($roomIds)) {
                $event->setCondition(
                    $expr->in('r.itemId', $roomIds), []
                );
            } else {
                $event->setCondition(
                    $expr->isNull('r.itemId')
                );
            }
        }
    }
}