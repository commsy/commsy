<?php

namespace CommsyBundle\Feed;

use CommsyBundle\Feed\Creators\Creator;

use Commsy\LegacyBundle\Utils\ItemService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class FeedCreatorFactory
{
    private $itemService;
    private $legacyEnvironment;

    private $creators = [];
    private $isGuestAccess = false;
    private $translator;
    private $router;

    public function __construct(ItemService $itemService, LegacyEnvironment $legacyEnvironment, TranslatorInterface $translator, Router $router)
    {
        $this->itemService = $itemService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
        $this->router = $router;
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

    public function createItem($item) {
        $type = $item['type'];
        $commsyItem = $this->itemService->getTypedItem($item['item_id']);

        if ($commsyItem->getType() === 'label') {
            $type = $commsyItem->getLabelType();
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

        throw new \RuntimeException('No creator can create a feed item for this rubric');
    }
}