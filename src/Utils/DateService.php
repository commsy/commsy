<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_dates_item;
use cs_dates_manager;
use cs_environment;
use Symfony\Component\Form\FormInterface;

class DateService
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var cs_dates_manager
     */
    private cs_dates_manager $datesManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->datesManager = $this->legacyEnvironment->getDatesManager();
        $this->datesManager->reset();
    }

    /**
     * @param integer $roomId
     * @param integer $max
     * @param integer $start
     * @param string $sort
     * @return cs_dates_item[]
     */
    public function getListDates($roomId, $max = null, $start = null, $sort = null): array
    {
        $this->datesManager->setContextLimit($roomId);
        if ($max !== null && $start !== null) {
            $this->datesManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $this->datesManager->setSortOrder($sort);
        }

        $this->datesManager->setWithoutDateModeLimit();

        $this->datesManager->select();
        $dateList = $this->datesManager->get();

        return $dateList->to_array();
    }

    /**
     * @param integer $roomId
     * @param integer[] $idArray
     * @return cs_dates_item[]
     */
    public function getDatesById($roomId, $idArray): array
    {
        $this->datesManager->setContextLimit($roomId);
        $this->datesManager->setIDArrayLimit($idArray);

        $this->datesManager->select();
        $dateList = $this->datesManager->get();

        return $dateList->to_array();
    }

    /**
     * @param FormInterface $filterForm
     */
    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            if ($formData['hide-deactivated-entries'] === 'only_activated') {
                $this->datesManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } else {
                if ($formData['hide-deactivated-entries'] === 'only_deactivated') {
                    $this->datesManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
                } else {
                    if ($formData['hide-deactivated-entries'] === 'all') {
                        $this->datesManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
                    }
                }
            }
        }

        // past
        if ($formData['hide-past-dates']) {
            $this->datesManager->setFutureLimit();
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
            $this->datesManager->setBetweenLimit($fromDate, $untilDate);
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->datesManager->setGroupLimit($relatedLabel->getItemId());
            }

            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->datesManager->setTopicLimit($relatedLabel->getItemId());
            }
        }

        // participants
        if (isset($formData['participant'])) {
            if (isset($formData['participant']['participant'])) {
                $users = $formData['participant']['participant'];

                if (!empty($users)) {
                    $this->datesManager->setParticipantArrayLimit($users);
                }
            }
        }

        // calendars
        if (isset($formData['calendar'])) {
            if (isset($formData['calendar']['calendar'])) {
                $calendars = $formData['calendar']['calendar'];

                if (!empty($calendars)) {
                    $this->datesManager->setCalendarArrayLimit($calendars);
                }
            }
        }

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->datesManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->datesManager->setTagArrayLimit($categories);
                }
            }
        }
    }

    public function setPastFilter($past)
    {
        if (!$past) {
            $this->datesManager->setFutureLimit();
        }
    }

    /**
     * @param $itemId
     * @return cs_dates_item|null
     */
    public function getDate($itemId): ?cs_dates_item
    {
        return $this->datesManager->getItem($itemId);
    }

    /**
     * @param integer $roomId
     * @param integer $start
     * @param integer $end
     * @return cs_dates_item[]
     */
    public function getCalendarEvents($roomId, $start, $end): array
    {
        $this->datesManager->setContextLimit($roomId);
        $this->datesManager->setWithoutDateModeLimit();
        $this->datesManager->setBetweenLimit($start, $end);
        $this->datesManager->select();
        $dateList = $this->datesManager->get();

        return $dateList->to_array();
    }

    public function getNewDate()
    {
        return $this->datesManager->getNewItem();
    }

    public function getCountArray($roomId): array
    {
        $this->datesManager->setContextLimit($roomId);
        $this->datesManager->setWithoutDateModeLimit();
        $this->datesManager->select();
        $countDatelArray = array();
        $countDatelArray['count'] = sizeof($this->datesManager->get()->to_array());
        $this->datesManager->resetLimits();
        $this->datesManager->setWithoutDateModeLimit();
        $this->datesManager->select();
        $countDatelArray['countAll'] = $this->datesManager->getCountAll();

        return $countDatelArray;
    }

    /**
     * @param integer $roomId
     * @param integer $recurringId
     * @return cs_dates_item[]
     */
    public function getRecurringDates($roomId, $recurringId): array
    {
        $this->datesManager->reset();
        $this->datesManager->setContextLimit($roomId);
        $this->datesManager->setRecurrenceLimit($recurringId);
        $this->datesManager->setWithoutDateModeLimit();
        $this->datesManager->select();
        $dateList = $this->datesManager->get();

        return $dateList->to_array();
    }

    public function hideDeactivatedEntries()
    {
        $this->datesManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }

    /** Retrieves the first date item matching the given VCALENDAR UID from a calendar & room with the given IDs
     * @param string $uid
     * @param integer $calendarId
     * @param integer $roomId
     * @return cs_dates_item|boolean
     */
    public function getDateByUid($uid, $calendarId, $roomId)
    {
        $this->datesManager->reset();
        $this->datesManager->setUidArrayLimit(['"' . $uid . '"']);
        $this->datesManager->setContextLimit($roomId);
        $this->datesManager->setWithoutDateModeLimit();
        //$this->datesManager->unsetContextLimit();
        $this->datesManager->select();
        $dateList = $this->datesManager->get();
        if (isset($dateList->to_array()[0])) {
            return $dateList->to_array()[0];
        }
        return false;
    }

    /**
     * @param $calendarId
     * @return cs_dates_item[]
     */
    public function getDatesByCalendarId($calendarId): array
    {
        $this->datesManager->reset();
        $this->datesManager->setCalendarArrayLimit(['"' . $calendarId . '"']);
        $this->datesManager->setWithoutDateModeLimit();
        $this->datesManager->select();
        $dateList = $this->datesManager->get();
        return $dateList->to_array();
    }
}