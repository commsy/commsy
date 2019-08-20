<?php


namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\UserService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class RoomTitleResolver extends AbstractExtension
{
    private $legacyEnvironment;
    private $userService;
    private $roomService;

    public function __construct( LegacyEnvironment $legacyEnvironment, UserService $userService, RoomService $roomService)
    {
        $this->userService = $userService;
        $this->legacyEnvironment = $legacyEnvironment;
        $this->roomService = $roomService;
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