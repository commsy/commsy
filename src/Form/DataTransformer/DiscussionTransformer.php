<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;

class DiscussionTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_discussion_item object to an array
     *
     * @param cs_discussion_item $discussionItem
     * @return array
     */
    public function transform($discussionItem)
    {
        $discussionData = array();

        if ($discussionItem) {
            $discussionData['title'] = html_entity_decode($discussionItem->getTitle());
            $discussionData['draft'] = $discussionItem->isDraft();
            $discussionData['permission'] = $discussionItem->isPrivateEditing();
            
            if ($discussionItem->isNotActivated()) {
                $discussionData['hidden'] = true;
                
                $activating_date = $discussionItem->getActivatingDate();
                if (!stristr($activating_date,'9999')){
                    $datetime = new \DateTime($activating_date);
                    $discussionData['hiddendate']['date'] = $datetime;
                    $discussionData['hiddendate']['time'] = $datetime;
                }
            }

            // external viewer
            if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
                $discussionData['external_viewer_enabled'] = true;
                $discussionData['external_viewer'] = $discussionItem->getExternalViewerString();
            } else {
                $discussionData['external_viewer_enabled'] = false;
            }
        }
        return $discussionData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $discussionObject
     * @param array $discussionData
     * @return cs_discussion_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($discussionObject, $discussionData)
    {
        $discussionObject->setTitle($discussionData['title']);
        
        if ($discussionData['permission']) {
            $discussionObject->setPrivateEditing('0');
        } else {
            $discussionObject->setPrivateEditing('1');
        }

        if (isset($discussionData['hidden'])) {
            if ($discussionData['hidden']) {
                if ($discussionData['hiddendate']['date']) {
                    // add validdate to validdate
                    $datetime = $discussionData['hiddendate']['date'];
                    if ($discussionData['hiddendate']['time']) {
                        $time = explode(":", $discussionData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $discussionObject->setModificationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $discussionObject->setModificationDate('9999-00-00 00:00:00');
                }
            } else {
                if($discussionObject->isNotActivated()){
    	            $discussionObject->setModificationDate(getCurrentDateTimeInMySQL());
    	        }
            }
        } else {
            if($discussionObject->isNotActivated()){
	            $discussionObject->setModificationDate(getCurrentDateTimeInMySQL());
	        }
        }

        // external viewer
        if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
            if (!empty(trim($discussionData['external_viewer']))) {
                $userIds = explode(" ", $discussionData['external_viewer']);
                $discussionObject->setExternalViewerAccounts($userIds);
            } else {
                $discussionObject->unsetExternalViewerAccounts();
            }
        }

        return $discussionObject;
    }
}