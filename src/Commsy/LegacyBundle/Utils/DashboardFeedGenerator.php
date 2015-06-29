<?php

namespace Commsy\LegacyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\RoomService;

class DashboardFeedGenerator
{
    private $legacyEnvironment;
    private $roomService;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->roomService = $roomService;
    }

    public function getFeedList($itemId, $max, $start)
    {
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();

        $userManager = $legacyEnvironment->getUserManager();
        $user = $userManager->getItem($itemId);
        
        $authSourceManager = $legacyEnvironment->getAuthSourceManager();
        $authSource = $authSourceManager->getItem($user->getAuthSource());
        $legacyEnvironment->setCurrentPortalID($authSource->getContextId());
        
        $projectArray = array();
        $projectList = $user->getRelatedProjectList();
        $project = $projectList->getFirst();
        while ($project) {
            $projectArray[] = $project->getItemId();
            $project = $projectList->getNext();
        }
        
        $itemManager = $legacyEnvironment->getItemManager();
        $itemManager->reset();
        $itemManager->setContextArrayLimit($projectArray);
        $itemManager->setTypeArrayLimit(array('user', 'material', 'date', 'discussion'));
        $itemManager->setIntervalLimit($max + $start);
        $itemManager->select();
        $itemList = $itemManager->get();
        
        if ($itemList->getCount() < $start + $max) {
            $max = $itemList->getCount() - $start;
        }
        
        $itemList = $itemList->getSubList($start, $max);
        
        $feedList = array();
        $item = $itemList->getFirst();
        while ($item) {
            $tempManager = $legacyEnvironment->getManager($item->getItemType());
            $tempItem = $tempManager->getItem($item->getItemId());
            $feedList[] = $tempItem;
            $item = $itemList->getNext();
        }
        
        usort($feedList, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        });
        
        return $feedList;
    }
}
