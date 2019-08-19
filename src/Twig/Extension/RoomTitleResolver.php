<?php


namespace App\Twig\Extension;

use App\Utils\RoomService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class RoomTitleResolver extends AbstractExtension
{

    private $roomService;

    public function __construct(RoomService $roomService)
    {
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
        return $this->roomService->getRoomTitle($roomId);
    }

}