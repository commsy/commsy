<?php
namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;
use Symfony\Component\Form\FormInterface;

class DateService
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @param integer $roomId
     * @param integer $max
     * @param integer $start
     * @param string $sort
     * @return \cs_dates_item[]
     */
    public function getListDates($roomId, $max = null, $start = null, $sort = null)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        $dateManager->setContextLimit($roomId);
        if ($max !== null && $start !== null) {
            $dateManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $dateManager->setSortOrder($sort);
        }

        $dateManager->setWithoutDateModeLimit();

        $dateManager->select();
        $dateList = $dateManager->get();

        return $dateList->to_array();
    }

    /**
     * @param integer $roomId
     * @param integer[] $idArray
     * @return \cs_dates_item[]
     */
    public function getDatesById($roomId, $idArray)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        $dateManager->setContextLimit($roomId);
        $dateManager->setIDArrayLimit($idArray);

        $dateManager->select();
        $dateList =$dateManager->get();

        return $dateList->to_array();
    }

    public function setFilterConditions(FormInterface $filterForm)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            $dateManager->showNoNotActivatedEntries();
        }

        // past
        if ($formData['hide-past-dates']) {
            $dateManager->setFutureLimit();
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
            $dateManager->setBetweenLimit($fromDate, $untilDate);
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $dateManager->setGroupLimit($relatedLabel->getItemId());
            }
            
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $dateManager->setTopicLimit($relatedLabel->getItemId());
            }
        }
        
        // participants
        if (isset($formData['participant'])) {
            if (isset($formData['participant']['participant'])) {
                $users = $formData['participant']['participant'];

                if (!empty($users)) {
                    $dateManager->setParticipantArrayLimit($users);
                }
            }
        }

        // calendars
        if (isset($formData['calendar'])) {
            if (isset($formData['calendar']['calendar'])) {
                $calendars = $formData['calendar']['calendar'];

                if (!empty($calendars)) {
                    $dateManager->setCalendarArrayLimit($calendars);
                }
            }
        }

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $dateManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $dateManager->setTagArrayLimit($categories);
                }
            }
        }
    }
    
    public function setPastFilter ($past)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        if (!$past) {
            $dateManager->setFutureLimit();
        }
    }

    /**
     * @param integer $itemId
     * @return \cs_dates_item
     */
    public function getDate($itemId)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        return $dateManager->getItem($itemId);
    }

    /**
     * @param integer $roomId
     * @param integer $start
     * @param integer $end
     * @return \cs_dates_item[]
     */
    public function getCalendarEvents($roomId, $start, $end)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        $dateManager->setContextLimit($roomId);
        $dateManager->setWithoutDateModeLimit();
        $dateManager->setBetweenLimit($start, $end);
        $dateManager->select();
        $dateList = $dateManager->get();

        return $dateList->to_array();
    }
    
    public function getNewDate()
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        return $dateManager->getNewItem();
    }
    
    public function getCountArray($roomId)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        $dateManager->setContextLimit($roomId);
        $dateManager->setWithoutDateModeLimit();
        $dateManager->select();
        $countDatelArray = array();
        $countDatelArray['count'] = sizeof($dateManager->get()->to_array());
        $dateManager->resetLimits();
        $dateManager->setWithoutDateModeLimit();
        $dateManager->select();
        $countDatelArray['countAll'] = $dateManager->getCountAll();

        return $countDatelArray;
    }

    /**
     * @param integer $roomId
     * @param integer $recurringId
     * @return \cs_dates_item[]
     */
    public function getRecurringDates($roomId, $recurringId)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        $dateManager->reset();
        $dateManager->setContextLimit($roomId);
        $dateManager->setRecurrenceLimit($recurringId);
        $dateManager->setWithoutDateModeLimit();
        $dateManager->select();
        $dateList = $dateManager->get();

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
     *   @return \cs_dates_item|boolean
     */
    public function getDateByUid($uid, $calendarId, $roomId)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        $dateManager->reset();
        $dateManager->setUidArrayLimit(['"'.$uid.'"']);
        $dateManager->setWithoutDateModeLimit();
        $dateManager->unsetContextLimit();
        $dateManager->select();
        $dateList =$dateManager->get();
        if (isset($dateList->to_array()[0])) {
            return $dateList->to_array()[0];
        }
        return false;
    }

    /**
     * @param $calendarId
     * @return \cs_dates_item[]
     */
    public function getDatesByCalendarId($calendarId)
    {
        $dateManager = $this->legacyEnvironment->getDateManager();

        $dateManager->reset();
        $dateManager->setCalendarArrayLimit(['"'.$calendarId.'"']);
        $dateManager->setWithoutDateModeLimit();
        $dateManager->select();
        $dateList = $dateManager->get();
        return $dateList->to_array();
    }
}