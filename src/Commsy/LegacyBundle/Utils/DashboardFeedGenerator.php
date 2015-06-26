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
        
        $projectList = $user->getRelatedProjectList();
        
        $feedList = array();
        
        $project = $projectList->getFirst();
        while ($project) {
            $homeConfiguration = $project->getHomeConf();
            $rubrics = array();
            if (!empty($homeConfiguration)) {
                $rubricConfigurations = explode(',', $homeConfiguration);
                
                foreach ($rubricConfigurations as $rubricConfiguration) {
                    list($rubricName) = explode('_', $rubricConfiguration);
                    $rubrics[] = $rubricName;
                }
            }

            // get the lastest items matching the configured rubrics
            $itemManager = $legacyEnvironment->getItemManager();
            $itemManager->reset();
            $itemManager->setContextLimit($project->getItemID());
            //$itemManager->setIntervalLimit($max + $start);
            $itemManager->setTypeArrayLimit($rubrics);
            $itemManager->select();
            $itemList = $itemManager->get();
    
            // TODO: group by rubric and get items chunkwise
    
            // iterate items and build up feed list
            $item = $itemList->getFirst();
            $itemIndex = 0;
            while ($item) {
                if ($itemIndex >= $start) {
                    $type = $item->getItemType();
        
                    switch ($type) {
                        case 'user':
                            $userManager = $legacyEnvironment->getUserManager();
                            $userItem = $userManager->getItem($item->getItemId());
                            $feedList[] = $userItem;
                            break;
        
                        case 'material':
                            $materialManager = $legacyEnvironment->getMaterialManager();
                            $materialItem = $materialManager->getItem($item->getItemId());
                            $feedList[] = $materialItem;
                            break;
        
                        case 'date':
                            $datesManager = $legacyEnvironment->getDatesManager();
                            $dateItem = $datesManager->getItem($item->getItemId());
                            $feedList[] = $dateItem;
                            break;
        
                        case 'discussion':
                            $discussionManager = $legacyEnvironment->getDiscussionManager();
                            $discussionItem = $discussionManager->getItem($item->getItemId());
                            $feedList[] = $discussionItem;
                            break;
                    }
                }
                $itemIndex++;
                $item = $itemList->getNext();
            }
            $project = $projectList->getNext();
        }
        
        usort($feedList, function ($firstItem, $secondItem) {
            return ($firstItem->getModificationDate() < $secondItem->getModificationDate());
        });
        
        return array_slice($feedList, $start, $max);
    }
}
