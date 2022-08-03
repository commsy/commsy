<?php
namespace App\Form\DataTransformer;

use DateTime;

class AnnouncementTransformer  extends AbstractTransformer
{
    protected $entity = 'announcement';

    /**
     * Transforms a cs_material_item object to an array
     *
     * @param \cs_announcement_item $announcementItem
     * @return array
     */
    public function transform($announcementItem)
    {
        $announcementData = array();

        if ($announcementItem) {
            $announcementData['title'] = html_entity_decode($announcementItem->getTitle());
            $announcementData['description'] = $announcementItem->getDescription();
            $announcementData['draft'] = $announcementItem->isDraft();

            $announcementData['permission'] = $announcementItem->isPrivateEditing();

            $datetime = new \DateTime($announcementItem->getSecondDateTime());
            $announcementData['validdate']['date'] = $datetime;
            $announcementData['validdate']['time'] = $datetime;
            
            if ($announcementItem->isNotActivated()) {
                $announcementData['hidden'] = true;
                
                $activating_date = $announcementItem->getActivatingDate();
                if (!stristr($activating_date,'9999')){
                    $datetime = new \DateTime($activating_date);
                    $announcementData['hiddendate']['date'] = $datetime;
                    $announcementData['hiddendate']['time'] = $datetime;
                }
            }
        }
        return $announcementData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param \cs_announcement_item $announcementObject
     * @param array $announcementData
     * @return \cs_announcement_item|null
     */
    public function applyTransformation($announcementObject, $announcementData)
    {
        $announcementObject->setTitle($announcementData['title']);
        $announcementObject->setDescription($announcementData['description']);

        if ($announcementData['permission']) {
            $announcementObject->setPrivateEditing('0');
        } else {
            $announcementObject->setPrivateEditing('1');
        }

        if ($announcementData['validdate']['date'] && $announcementData['validdate']['time']) {
            // add validdate to validdate
            $datetime = $announcementData['validdate']['date'];
            $time = explode(":", $announcementData['validdate']['time']->format('H:i'));
            $datetime->setTime($time[0], $time[1]);
            $announcementObject->setSecondDateTime($datetime->format('Y-m-d H:i:s'));

        }

        
        if (isset($announcementData['hidden'])) {
            if ($announcementData['hidden']) {
                if (isset($announcementData['hiddendate']['date'])) {
                    // add validdate to validdate
                    $datetime = $announcementData['hiddendate']['date'];
                    if ($announcementData['hiddendate']['time']) {
                        $time = explode(":", $announcementData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $announcementObject->setActivationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $announcementObject->setActivationDate('9999-00-00 00:00:00');
                }
            } else {
                if ($announcementObject->isNotActivated()) {
    	            $announcementObject->setActivationDate(null);
    	        }
            }
        } else {
            if ($announcementObject->isNotActivated()) {
	            $announcementObject->setActivationDate(null);
	        }
        }
        
        return $announcementObject;
    }
}