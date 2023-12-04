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

namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use cs_environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RoomTitleResolver extends AbstractExtension
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly RoomService $roomService,
        private readonly TranslatorInterface $translator
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('roomtitle', $this->resolveRoomTitle(...)),
        ];
    }

    public function resolveRoomTitle($roomId)
    {
        $room = $this->roomService->getRoomItem($roomId);
        if ($room->isGroupRoom()) {
            $type = $this->translator->trans('grouproom', [], 'room');
        } else {
            $type = $this->translator->trans($room->getType(), [], 'room');
        }

        return $room->getTitle().' ('.$type.')';
    }
}
