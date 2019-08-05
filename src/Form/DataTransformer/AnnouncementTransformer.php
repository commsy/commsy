<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;

class AnnouncementTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

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

            $announcementData['validdate_eng']['date'] = $datetime;
            $announcementData['validdate_eng']['time'] = $datetime;
            
            if ($announcementItem->isNotActivated()) {
                $announcementData['hidden'] = true;
                
                $activating_date = $announcementItem->getActivatingDate();
                if (!stristr($activating_date,'9999')){
                    $datetime = new \DateTime($activating_date);
                    $announcementData['hiddendate']['date'] = $datetime;
                    $announcementData['hiddendate']['time'] = $datetime;

                    $announcementData['hiddendate_eng']['date'] = $datetime;
                    $announcementData['hiddendate_eng']['time'] = $datetime;
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

        if ($announcementData['validdate_eng']['date'] && $announcementData['validdate_eng']['time']) {
            // add validdate to validdate
            $datetime = $announcementData['validdate_eng']['date'];
            $time = explode(":", $announcementData['validdate_eng']['time']->format('H:i'));
            $datetime->setTime($time[0], $time[1]);
            $announcementObject->setSecondDateTime($datetime->format('Y-m-d H:i:s'));
        }
        
        if (isset($announcementData['hidden'])) {
            if ($announcementData['hidden']) {
                if ($announcementData['hiddendate']['date']) {
                    // add validdate to validdate
                    $datetime = $announcementData['hiddendate']['date'];
                    if ($announcementData['hiddendate']['time']) {
                        $time = explode(":", $announcementData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $announcementObject->setModificationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $announcementObject->setModificationDate('9999-00-00 00:00:00');
                }
            } else {
                if($announcementObject->isNotActivated()){
    	            $announcementObject->setModificationDate(getCurrentDateTimeInMySQL());
    	        }
            }
        } else {
            if($announcementObject->isNotActivated()){
	            $announcementObject->setModificationDate(getCurrentDateTimeInMySQL());
	        }
        }

        if (isset($announcementData['hidden_eng'])) {
            if ($announcementData['hidden_eng']) {
                if ($announcementData['hiddendate_eng']['date']) {
                    // add validdate to validdate
                    $datetime = $announcementData['hiddendate_eng']['date'];
                    if ($announcementData['hiddendate_eng']['time']) {
                        $time = explode(":", $announcementData['hiddendate_eng']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $announcementObject->setModificationDate($datetime->format('d-m-Y H:i:s'));
                } else {
                    $announcementObject->setModificationDate('9999-00-00 00:00:00');
                }
            } else {
                if($announcementObject->isNotActivated()){
                    $announcementObject->setModificationDate(getCurrentDateTimeInMySQL());
                }
            }
        } else {
            if($announcementObject->isNotActivated()){
                $announcementObject->setModificationDate(getCurrentDateTimeInMySQL());
            }
        }
        
        return $announcementObject;
    }
}