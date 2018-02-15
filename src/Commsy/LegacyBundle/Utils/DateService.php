<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class DateService
{
    private $legacyEnvironment;

    private $dateManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->dateManager = $this->legacyEnvironment->getDateManager();
        $this->dateManager->reset();
    }

    public function getListDates($roomId, $max, $start, $sort)
    {
        $this->dateManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->dateManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $this->dateManager->setSortOrder($sort);
        }

        $this->dateManager->setWithoutDateModeLimit();

        $this->dateManager->select();
        $dateList = $this->dateManager->get();

        return $dateList->to_array();
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            $this->dateManager->showNoNotActivatedEntries();
        }

        // past
        if ($formData['hide-past-dates']) {
            $this->dateManager->setFutureLimit();
        }

        // dates between
        $isBetweenFilterSet = false;
        $fromDate = null;
        if (isset($formData['date-from']['date'])) {
            $isBetweenFilterSet = true;
            $fromDate = $formData['date-from']['date']->format('Y-m-d 00:00:00');
        }
        $untilDate = null;
        if (isset($formData['date-until']['date'])) {
            $isBetweenFilterSet = true;
            $untilDate = $formData['date-until']['date']->format('Y-m-d 23:59:59');
        }
        
        if ($isBetweenFilterSet) {
            $this->dateManager->setBetweenLimit($fromDate, $untilDate);
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->dateManager->setGroupLimit($relatedLabel->getItemId());
            }
            
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->dateManager->setTopicLimit($relatedLabel->getItemId());
            }
            
            // institution
            if (isset($formData['rubrics']['institution'])) {
                $relatedLabel = $formData['rubrics']['institution'];
                $this->dateManager->setInstitutionLimit($relatedLabel->getItemId());
            }
        }
        
        // participants
        if (isset($formData['participant'])) {
            if (isset($formData['participant']['participant'])) {
                $users = $formData['participant']['participant'];

                if (!empty($users)) {
                    $this->dateManager->setParticipantArrayLimit($users);
                }
            }
        }

        // calendars
        if (isset($formData['calendar'])) {
            if (isset($formData['calendar']['calendar'])) {
                $calendars = $formData['calendar']['calendar'];

                if (!empty($calendars)) {
                    $this->dateManager->setCalendarArrayLimit($calendars);
                }
            }
        }

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->dateManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->dateManager->setTagArrayLimit($categories);
                }
            }
        }
    }
    
    public function setPastFilter ($past) {
        if (!$past) {
            $this->dateManager->setFutureLimit();
        }
    }
    
    public function getDate($itemId)
    {
        return $this->dateManager->getItem($itemId);
    }
    
    public function getCalendarEvents($roomId, $start, $end)
    {
        $this->dateManager->setContextLimit($roomId);
        $this->dateManager->setWithoutDateModeLimit();
        $this->dateManager->setBetweenLimit($start, $end);
        $this->dateManager->select();
        $dateList = $this->dateManager->get();

        return $dateList->to_array();
    }
    
    public function getNewDate()
    {
        return $this->dateManager->getNewItem();
    }
    
    public function getCountArray($roomId)
    {
        $this->dateManager->setContextLimit($roomId);
        $this->dateManager->setWithoutDateModeLimit();
        $this->dateManager->select();
        $countDatelArray = array();
        $countDatelArray['count'] = sizeof($this->dateManager->get()->to_array());
        $this->dateManager->resetLimits();
        $this->dateManager->setWithoutDateModeLimit();
        $this->dateManager->select();
        $countDatelArray['countAll'] = $this->dateManager->getCountAll();

        return $countDatelArray;
    }
    
    public function getRecurringDates($roomId, $recurringId)
    {
        $this->dateManager->reset();
        $this->dateManager->setContextLimit($roomId);
        $this->dateManager->setRecurrenceLimit($recurringId);
        $this->dateManager->setWithoutDateModeLimit();
        $this->dateManager->select();
        $dateList = $this->dateManager->get();
        return $dateList->to_array();
    }
    
    public function hideDeactivatedEntries()
    {
        $this->dateManager->showNoNotActivatedEntries();
    }

    /** Retrieves the first date item matching the given VCALENDAR UID from a calendar & room with the given IDs
     *   @param string $uid
     *   @param integer $calendarId
     *   @param integer $roomId
     *   @return cs_date_item|boolean
     */
    public function getDateByUid($uid, $calendarId, $roomId)
    {
        $this->dateManager->reset();
        $this->dateManager->setContextLimit($roomId);
        $this->dateManager->setUidArrayLimit(['"'.$uid.'"']);
        $this->dateManager->setCalendarArrayLimit(['"'.$calendarId.'"']);
        $this->dateManager->setWithoutDateModeLimit();
        $this->dateManager->select();
        $dateList = $this->dateManager->get();
        if (isset($dateList->to_array()[0])) {
            return $dateList->to_array()[0];
        }
        return false;
    }

    public function getDatesByCalendarId($calendarId)
    {
        $this->dateManager->reset();
        $this->dateManager->setCalendarArrayLimit(['"'.$calendarId.'"']);
        $this->dateManager->setWithoutDateModeLimit();
        $this->dateManager->select();
        $dateList = $this->dateManager->get();
        return $dateList->to_array();
    }
}