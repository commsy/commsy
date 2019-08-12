<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;

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
     * @param \cs_dates_item $dateItem
     * @return array
     */
    public function transform($dateItem)
    {
        $dateData = array();

        if ($dateItem) {
            $dateData['title'] = html_entity_decode($dateItem->getTitle());
            $dateData['description'] = $dateItem->getDescription();
            $dateData['permission'] = !($dateItem->isPublic());
            $dateData['place'] = $dateItem->getPlace();
            
            $datetimeStart = new \DateTime($dateItem->getDateTime_start());
            $dateData['start']['date'] = $datetimeStart;
            $dateData['start']['time'] = $datetimeStart;

            $dateData['start_eng']['date'] = $datetimeStart;
            $dateData['start_eng']['time'] = $datetimeStart;
            
            $datetimeEnd = new \DateTime($dateItem->getDateTime_end());
            $dateData['end']['date'] = $datetimeEnd;
            $dateData['end']['time'] = $datetimeEnd;

            $dateData['end_eng']['date'] = $datetimeEnd;
            $dateData['end_eng']['time'] = $datetimeEnd;

            $dateData['whole_day'] = $dateItem->isWholeDay();

            $dateData['calendar'] = $dateItem->getCalendarId();

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

                    $dateData['hiddendate_eng']['date'] = $datetime;
                    $dateData['hiddendate_eng']['time'] = $datetime;
                }
            }

            // external viewer
            if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
                $dateData['external_viewer_enabled'] = true;
                $dateData['external_viewer'] = $dateItem->getExternalViewerString();
            } else {
                $dateData['external_viewer_enabled'] = false;
            }
        }

        return $dateData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param \cs_dates_item $dateObject
     * @param array $dateData
     * @return \cs_dates_item
     */
    public function applyTransformation($dateObject, $dateData)
    {
        $dateObject->setTitle($dateData['title']);
        $dateObject->setDescription($dateData['description']);

        if ($dateData['permission']) {
            $dateObject->setPublic(0);
        } else {
            $dateObject->setPublic(1);
        }

        $dateObject->setPlace($dateData['place']);

        $dateObject->setWholeDay($dateData['whole_day']);
        if ($dateObject->isWholeDay()) {
            if(!empty($dateData['start']['date'])){
                $dateObject->setStartingDay($dateData['start']['date']->format('Y-m-d'));
                $dateObject->setStartingTime($dateData['start']['time']->format('00:00'));
                $dateObject->setDatetime_start($dateData['start']['date']->format('Y-m-d') . ' ' . $dateData['start']['time']->format('00:00:00'));

                $dateObject->setEndingDay($dateData['end']['date']->format('Y-m-d'));
                $dateObject->setEndingTime($dateData['end']['time']->format('23:59'));
                $dateObject->setDatetime_end($dateData['end']['date']->format('Y-m-d') . ' ' . $dateData['end']['time']->format('23:59:59'));
            }else{
                $dateObject->setStartingDay($dateData['start_eng']['date']->format('Y-m-d'));
                $dateObject->setStartingTime($dateData['start_eng']['time']->format('00:00'));
                $dateObject->setDatetime_start($dateData['start_eng']['date']->format('Y-m-d') . ' ' . $dateData['start_eng']['time']->format('00:00:00'));

                $dateObject->setEndingDay($dateData['end_eng']['date']->format('Y-m-d'));
                $dateObject->setEndingTime($dateData['end_eng']['time']->format('23:59'));
                $dateObject->setDatetime_end($dateData['end_eng']['date']->format('Y-m-d') . ' ' . $dateData['end_eng']['time']->format('23:59:59'));
            }
        } else {
            if(!empty($dateData['start']['date'])){

                $dateObject->setStartingDay($dateData['start']['date']->format('Y-m-d'));
                $dateObject->setStartingTime($dateData['start']['time']->format('H:i'));
                $dateObject->setDatetime_start($dateData['start']['date']->format('Y-m-d') . ' ' . $dateData['start']['time']->format('H:i:s'));

                $dateObject->setEndingDay($dateData['end']['date']->format('Y-m-d'));
                $dateObject->setEndingTime($dateData['end']['time']->format('H:i'));
                $dateObject->setDatetime_end($dateData['end']['date']->format('Y-m-d') . ' ' . $dateData['end']['time']->format('H:i:s'));
            }else{
                $dateObject->setStartingDay($dateData['start_eng']['date']->format('Y-m-d'));
                $dateObject->setStartingTime($dateData['start_eng']['time']->format('H:i'));
                $dateObject->setDatetime_start($dateData['start_eng']['date']->format('Y-m-d') . ' ' . $dateData['start_eng']['time']->format('H:i:s'));

                $dateObject->setEndingDay($dateData['end_eng']['date']->format('Y-m-d'));
                $dateObject->setEndingTime($dateData['end_eng']['time']->format('H:i'));
                $dateObject->setDatetime_end($dateData['end_eng']['date']->format('Y-m-d') . ' ' . $dateData['end_eng']['time']->format('H:i:s'));
            }
        }

        $dateObject->setCalendarId($dateData['calendar']);

        if (isset($dateData['hidden'])) {
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
                if ($dateData['hiddendate_eng']['date']) {
                    // add validdate to validdate
                    $datetime = $dateData['hiddendate_eng']['date'];
                    if ($dateData['hiddendate_eng']['time']) {
                        $time = explode(":", $dateData['hiddendate_eng']['time']->format('H:i'));
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
        } else {
            if($dateObject->isNotActivated()){
	            $dateObject->setModificationDate(getCurrentDateTimeInMySQL());
	        }
        }

        // external viewer
        if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
            if (!empty(trim($dateData['external_viewer']))) {
                $userIds = explode(" ", $dateData['external_viewer']);
                $dateObject->setExternalViewerAccounts($userIds);
            } else {
                $dateObject->unsetExternalViewerAccounts();
            }
        }

        return $dateObject;
    }
}