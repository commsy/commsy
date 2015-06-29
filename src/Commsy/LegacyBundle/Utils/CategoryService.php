<?php

namespace Commsy\LegacyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class CategoryService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getTags($roomId)
    {
        $tagManager = $this->legacyEnvironment->getEnvironment()->getTagManager();

        $rootItem = $tagManager->getRootTagItemFor($roomId);
        return $this->buildTagArray($rootItem);
    }

    public function addTag($title, $roomId, $parentTagId = null)
    {
        // if has access
        // ...

        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $currentUserItem = $environment->getCurrentUserItem();
        $tagManager = $environment->getTagManager();

        if (!$parentTagId) {
            $rootTagItem = $tagManager->getRootTagItemFor($roomId);
            $parentTagId = $rootTagItem->getItemID();
        }

        $parentTagItem = $tagManager->getItem($parentTagId);

        $tagItem = $tagManager->getNewItem();
        $tagItem->setTitle($title);
        $tagItem->setContextID($roomId);
        $tagItem->setCreatorItem($currentUserItem);
        $tagItem->setCreationDate(date("Y-m-d H:i:s"));
        $tagItem->setPosition($parentTagId, $parentTagItem->getChildrenList()->getCount() + 1);

        $tagItem->save();
    }

    private function buildTagArray($item, $level = 0)
    {
        $return = array();

        if (isset($item)) {
            $childrenList = $item->getChildrenList();
            $level++;

            $item = $childrenList->getFirst();
            while ($item) {
                // attach to return
                $return[] = array(
                    'title'     => $item->getTitle(),
                    'item_id'   => $item->getItemID(),
                    'level'     => $level,
                    'children'  => $this->buildTagArray($item, $level)
                );

                $item = $childrenList->getNext();
            }
        }

        return $return;
    }
}