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

namespace App\Feed;

use App\Feed\Creators\Creator;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use RuntimeException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedCreatorFactory
{
    private cs_environment $legacyEnvironment;

    private array $creators = [];
    private $isGuestAccess = false;

    public function __construct(
        private ItemService $itemService,
        LegacyEnvironment $legacyEnvironment,
        private TranslatorInterface $translator,
        private RouterInterface $router
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function addCreator(Creator $creator)
    {
        $creator->setTextConverter($this->legacyEnvironment->getTextConverter());
        $creator->setTranslator($this->translator);
        $creator->setRouter($this->router);

        $this->creators[] = $creator;
    }

    public function setGuestAccess($isGuestAccess)
    {
        $this->isGuestAccess = $isGuestAccess;
    }

    public function createItem($item)
    {
        $type = $item['type'];
        $commsyItem = $this->itemService->getTypedItem($item['item_id']);

        if (!$commsyItem) {
            return;
        }

        if ('label' === $commsyItem->getType()) {
            $type = $commsyItem->getLabelType();
            if (in_array($type, ['buzzword'])) {
                return;
            }

            $manager = $this->legacyEnvironment->getManager($type);
            $commsyItem = $manager->getItem($commsyItem->getItemId());
        }

        $creator = $this->findAccurateCreator($type);
        $creator->setGuestAccess($this->isGuestAccess);

        $feedItem = $creator->createItem($commsyItem);

        return $feedItem;
    }

    private function findAccurateCreator($rubric)
    {
        foreach ($this->creators as $creator) {
            if ($creator->canCreate($rubric)) {
                return $creator;
            }
        }

        throw new RuntimeException('No creator found that supports the rubric "'.$rubric.'"');
    }
}
