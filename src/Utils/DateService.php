<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_dates_item;
use cs_dates_manager;
use cs_environment;
use cs_manager;
use Symfony\Component\Form\FormInterface;

class DateService
{
    private readonly cs_environment $legacyEnvironment;

    private readonly cs_dates_manager $datesManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->datesManager = $this->legacyEnvironment->getDatesManager();
        $this->datesManager->reset();
    }

    /**
     * @param int    $roomId
     * @param int    $max
     * @param int    $start
     * @param string $sort
     *
     * @return cs_dates_item[]
     */
    public function getListDates($roomId, $max = null, $start = null, $sort = null): array
    {
        $this->datesManager->setContextLimit($roomId);
        if (null !== $max && null !== $start) {
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
     * @param int   $roomId
     * @param int[] $idArray
     *
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

    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            if ('only_activated' === $formData['hide-deactivated-entries']) {
                $this->datesManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } else {
                if ('only_deactivated' === $formData['hide-deactivated-entries']) {
                    $this->datesManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
                } else {
                    if ('all' === $formData['hide-deactivated-entries']) {
                        $this->datesManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
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

    public function getDate($itemId): ?cs_dates_item
    {
        return $this->datesManager->getItem($itemId);
    }

    /**
     * @param int $roomId
     * @param int $start
     * @param int $end
     *
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
        $countDatelArray = [];
        $countDatelArray['count'] = sizeof($this->datesManager->get()->to_array());
        $this->datesManager->resetLimits();
        $this->datesManager->setWithoutDateModeLimit();
        $this->datesManager->select();
        $countDatelArray['countAll'] = $this->datesManager->getCountAll();

        return $countDatelArray;
    }

    /**
     * @param int $roomId
     * @param int $recurringId
     *
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
        $this->datesManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }

    /** Retrieves the first date item matching the given VCALENDAR UID from a calendar & room with the given IDs.
     * @param string $uid
     * @param int    $calendarId
     * @param int    $roomId
     */
    public function getDateByUid($uid, $calendarId, $roomId): \cs_dates_item|bool
    {
        $this->datesManager->reset();
        $this->datesManager->setUidArrayLimit(['"'.$uid.'"']);
        $this->datesManager->setContextLimit($roomId);
        $this->datesManager->setWithoutDateModeLimit();
        $this->datesManager->select();
        $dateList = $this->datesManager->get();

        return $dateList->to_array()[0] ?? false;
    }

    /**
     * @return cs_dates_item[]
     */
    public function getDatesByCalendarId($calendarId): array
    {
        $this->datesManager->reset();
        $this->datesManager->setCalendarArrayLimit(['"'.$calendarId.'"']);
        $this->datesManager->setWithoutDateModeLimit();
        $this->datesManager->select();
        $dateList = $this->datesManager->get();

        return $dateList->to_array();
    }
}
