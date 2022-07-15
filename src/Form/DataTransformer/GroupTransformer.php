<?php
namespace App\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;

class GroupTransformer extends AbstractTransformer
{
    protected $entity = 'group';

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