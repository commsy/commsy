<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class DateTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_date_item object to an array
     *
     * @param cs_date_item $dateItem
     * @return array
     */
    public function transform($dateItem)
    {
        $dateData = array();

        if ($dateItem) {
            $dateData['title'] = $dateItem->getTitle();
            $dateData['description'] = $dateItem->getDescription();
            $dateData['permission'] = $dateItem->isPrivateEditing();
            $dateData['place'] = $dateItem->getPlace();
            
            $datetimeStart = new \DateTime($dateItem->getDateTime_start());
            $dateData['start']['date'] = $datetimeStart;
            $dateData['start']['time'] = $datetimeStart;
            
            $datetimeEnd = new \DateTime($dateItem->getDateTime_start());
            $dateData['end']['date'] = $datetimeEnd;
            $dateData['end']['time'] = $datetimeEnd;
            
            $dateData['color'] = $dateItem->getColor();
            if ($dateData['color'] == '#999999') {
                $dateData['color'] = 'cs-date-color-01';
            } else if ($dateData['color'] == '#CC0000') {
                $dateData['color'] = 'cs-date-color-02';
            } else if ($dateData['color'] == '#FF6600') {
                $dateData['color'] = 'cs-date-color-03';
            } else if ($dateData['color'] == '#FFCC00') {
                $dateData['color'] = 'cs-date-color-04';
            } else if ($dateData['color'] == '#FFFF66') {
                $dateData['color'] = 'cs-date-color-05';
            } else if ($dateData['color'] == '#33CC00') {
                $dateData['color'] = 'cs-date-color-06';
            } else if ($dateData['color'] == '#00CCCC') {
                $dateData['color'] = 'cs-date-color-07';
            } else if ($dateData['color'] == '#3366FF') {
                $dateData['color'] = 'cs-date-color-08';
            } else if ($dateData['color'] == '#6633FF') {
                $dateData['color'] = 'cs-date-color-09';
            } else if ($dateData['color'] == '#CC33CC') {
                $dateData['color'] = 'cs-date-color-10';
            }
            if ($dateData['color'] == '') {
                $dateData['color'] = 'cs-date-color-no-color';
            }

            if ($dateItem->getRecurrencePattern() != '') {
                $dateData = array_merge($dateData, $dateItem->getRecurrencePattern());
                $dateData['recurring_sub']['untilDate'] = new \DateTime($dateData['recurringEndDate']);
            }
            
            if ($dateItem->isNotActivated()) {
                $dateData['hidden'] = true;
                
                $activating_date = $dateItem->getActivatingDate();
                if (!stristr($activating_date,'9999')){
                    $datetime = new \DateTime($activating_date);
                    $dateData['hiddendate']['date'] = $datetime;
                    $dateData['hiddendate']['time'] = $datetime;
                }
            }
        }

        return $dateData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $dateObject
     * @param array $dateData
     * @return cs_date_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($dateObject, $dateData)
    {
        $dateObject->setTitle($dateData['title']);
        $dateObject->setDescription($dateData['description']);
        
        if ($dateData['permission']) {
            $dateObject->setPrivateEditing('0');
        } else {
            $dateObject->setPrivateEditing('1');
        }

        $dateObject->setPlace($dateData['place']);
        
        $dateObject->setStartingDay($dateData['start']['date']->format('Y-m-d'));
        $dateObject->setStartingTime($dateData['start']['time']->format('H:i'));
        
        $dateObject->setEndingDay($dateData['end']['date']->format('Y-m-d'));
        $dateObject->setEndingTime($dateData['end']['time']->format('H:i'));

        $dateObject->setColor($dateData['color']);

        if ($dateData['hidden']) {
            if ($dateData['hiddendate']['date']) {
                // add validdate to validdate
                $datetime = $dateData['hiddendate']['date'];
                if ($dateData['hiddendate']['time']) {
                    $time = explode(":", $dateData['hiddendate']['time']->format('H:i'));
                    $datetime->setTime($time[0], $time[1]);
                }
                $dateObject->setModificationDate($datetime->format('Y-m-d H:i:s'));
            } else {
                $dateObject->setModificationDate('9999-00-00 00:00:00');
            }
        } else {
            if($dateObject->isNotActivated()){
	            $dateObject->setModificationDate(getCurrentDateTimeInMySQL());
	        }
        }

        return $dateObject;
    }
}