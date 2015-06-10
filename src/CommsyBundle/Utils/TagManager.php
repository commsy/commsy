<?php

namespace CommsyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class TagManager
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