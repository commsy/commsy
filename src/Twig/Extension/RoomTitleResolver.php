<?php


namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\UserService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class RoomTitleResolver extends AbstractExtension
{
    private $legacyEnvironment;
    private $userService;
    private $roomService;
    private $translator;

    public function __construct( LegacyEnvironment $legacyEnvironment, UserService $userService, RoomService $roomService, TranslatorInterface $translator)
    {
        $this->userService = $userService;
        $this->legacyEnvironment = $legacyEnvironment;
        $this->roomService = $roomService;
        $this->translator = $translator;
    }

    public function getFilters()
    {
            // Hallo
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
        foreach($rooms as $room) {
            if ($room->getItemID() == $roomId) {
                $type = $room->getType();
                $type = $this->translator->trans($type, [], 'room');
            } else {
                if ($room->getType() == 'project') {
                    $grouprooms = $room->getGroupRoomList();
                    foreach ($grouprooms as $grouproom) {
                        if ($grouproom->getItemID() == $roomId) {
                            $type = $this->translator->trans('grouproom', [], 'room');
                        }
                    }
                }
            }
        }
        return $this->roomService->getRoomTitle($roomId).' ('.$type.')';
    }

}