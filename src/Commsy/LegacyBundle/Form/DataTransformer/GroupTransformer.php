<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class GroupTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_group_item object to an array
     *
     * @param \cs_group_item $groupItem
     * @return array
     */
    public function transform($groupItem)
    {
        $groupData = array();

        if ($groupItem) {
            $groupData['title'] = html_entity_decode($groupItem->getTitle());
            $groupData['description'] = $groupItem->getDescription();
            $groupData['permission'] = $groupItem->isPrivateEditing();
            $groupData['activate'] = $groupItem->isGroupRoomActivated();
            
            if ($groupItem->isNotActivated()) {
                $groupData['hidden'] = true;
                
                $activating_date = $groupItem->getActivatingDate();
                if (!stristr($activating_date,'9999')){
                    $datetime = new \DateTime($activating_date);
                    $groupData['hiddendate']['date'] = $datetime;
                    $groupData['hiddendate']['time'] = $datetime;
                }
            }
        }

        return $groupData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $groupObject
     * @param array $groupData
     * @return \cs_group_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($groupObject, $groupData)
    {
        $groupObject->setTitle($groupData['title']);
        $groupObject->setDescription($groupData['description']);
        
        if ($groupData['permission']) {
            $groupObject->setPrivateEditing('0');
        } else {
            $groupObject->setPrivateEditing('1');
        }

        if (isset($groupData['hidden']) && !empty($groupData['hidden'])) {
            if (isset($groupData['hiddendate']) && isset($groupData['hiddendate']['date'])) {
                $datetime = $groupData['hiddendate']['date'];
                if ($groupData['hiddendate']['time']) {
                    $time = explode(":", $groupData['hiddendate']['time']->format('H:i'));
                    $datetime->setTime($time[0], $time[1]);
                }
                $groupObject->setModificationDate($datetime->format('Y-m-d H:i:s'));
            } else {
                $groupObject->setModificationDate('9999-00-00 00:00:00');
            }
        } else if($groupObject->isNotActivated()){
            $groupObject->setModificationDate(getCurrentDateTimeInMySQL());
        }

        if ($groupData['activate']) {
            $groupObject->setGroupRoomActive();
        } else {
            $groupObject->unsetGroupRoomActive();
        }

        return $groupObject;
    }
}