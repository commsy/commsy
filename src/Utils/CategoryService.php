<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;

class CategoryService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getTag($tagId)
    {
        $tagManager = $this->legacyEnvironment->getEnvironment()->getTagManager();

        return $tagManager->getItem($tagId);
    }

    public function updateTag($tagId, $newTitle)
    {
        $tagItem = $this->getTag($tagId);
        $tagItem->setTitle($newTitle);
        $tagItem->save();
    }

    public function getTags($roomId)
    {
        $tagManager = $this->legacyEnvironment->getEnvironment()->getTagManager();

        $rootItem = $tagManager->getRootTagItemFor($roomId);
        return $this->buildTagArray($rootItem);
    }

    public function addTag($title, $roomId, $parentTagId = null)
    {
        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $currentUserItem = $environment->getCurrentUserItem();
        $tagManager = $environment->getTagManager();

        if (!$parentTagId) {
            $rootTagItem = $tagManager->getRootTagItemFor($roomId);
            if (!$rootTagItem) {
                $tagManager->createRootTagItemFor($roomId);
                $tagManager->forceSQL();
                $rootTagItem = $tagManager->getRootTagItemFor($roomId);
            }
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

    public function removeTag($tagId, $roomId)
    {
        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $tagManager = $environment->getTagManager();
        $tagManager->delete($tagId);
    }

    public function updateStructure($structure, $roomId)
    {
        $environment = $this->legacyEnvironment->getEnvironment();
        $environment->setCurrentContextID($roomId);

        $tagManager = $environment->getTagManager();
        $rootTagItem = $tagManager->getRootTagItemFor($roomId);

        $this->updateTree($structure, $rootTagItem, $tagManager);
    }

    private function updateTree($structure, $rootItem, $tagManager)
    {
        foreach ($structure as $position => $tagInformation) {
            // persist new position
            $tagItem = $tagManager->getItem($tagInformation['itemId']);
            $tagItem->setPosition($rootItem->getItemId(), $position+1);
            $tagItem->save();

            if (!empty($tagInformation['children'])) {
                $this->updateTree($tagInformation['children'], $tagItem, $tagManager);
            }
        }
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