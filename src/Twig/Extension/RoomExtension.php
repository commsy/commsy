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
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RoomExtension extends AbstractExtension
{
    public function __construct(private readonly LegacyEnvironment $legacyEnvironment)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('roomTitle', $this->roomTitle(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('roomExpandedHashtags', $this->roomExpandedHashtags(...)),
            new TwigFunction('roomExpandedCategories', $this->roomExpandedCategories(...)),
        ];
    }

    public function roomTitle($roomId)
    {
        // get room title
        $item_manager = $this->legacyEnvironment->getEnvironment()->getItemManager();
        $item = $item_manager->getItem($roomId);

        if ('privateroom' != $item->getItemType()) {
            $roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        } else {
            $roomManager = $this->legacyEnvironment->getEnvironment()->getPrivateRoomManager();
        }
        $roomItem = $roomManager->getItem($roomId);

        return $roomItem->getTitle();
    }

    public function roomExpandedHashtags($roomId)
    {
        $roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if ($roomItem) {
            return $roomItem->isBuzzwordShowExpanded();
        }

        return false;
    }

    public function roomExpandedCategories($roomId)
    {
        $roomManager = $this->legacyEnvironment->getEnvironment()->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if ($roomItem) {
            return $roomItem->isTagsShowExpanded();
        }

        return false;
    }
}
