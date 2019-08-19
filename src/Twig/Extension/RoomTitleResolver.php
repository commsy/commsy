<?php


namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class RoomTitleResolver extends AbstractExtension
{
    private $legacyEnvironment;
    private $userService;

    public function __construct( LegacyEnvironment $legacyEnvironment, UserService $userService)
    {
        $this->userService = $userService;
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('roomtitle', [$this, 'resolveRoomTitle']),
        ];
    }

    public function resolveRoomTitle($roomId)
    {
        $currentUser = $this->userService->getCurrentUserItem();
        $env = $this->legacyEnvironment->getEnvironment();
        $rooms = $env->getRoomManager()->getRelatedRoomListForUser($currentUser);
        $type = ' ';
        foreach($rooms as $room){
            if($room->getItemID() == $roomId){
                $type = $room->getType();
            }
        }
        return $this->roomService->getRoomTitle($roomId).' ('.$type.')';
    }

}