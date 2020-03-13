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
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    private $userService;
    private $roomService;
    private $translator;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        RoomService $roomService,
        TranslatorInterface $translator
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('roomtitle', [$this, 'resolveRoomTitle']),
        ];
    }

    public function resolveRoomTitle($roomId)
    {
        $room = $this->roomService->getRoomItem($roomId);
        if ($room === null) {
            $this->legacyEnvironment->toggleArchiveMode();
            $room = $this->roomService->getRoomItem($roomId);
            $this->legacyEnvironment->toggleArchiveMode();
        }

        if ($room->isGroupRoom()) {
            $type = $this->translator->trans('grouproom', [], 'room');
        } else {
            $type = $this->translator->trans($room->getType(), [], 'room');
        }

        return $room->getTitle() . ' (' . $type . ')';
    }

}