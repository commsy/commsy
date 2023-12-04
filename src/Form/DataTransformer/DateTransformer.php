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

namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use cs_dates_item;
use cs_environment;
use DateTime;

class DateTransformer extends AbstractTransformer
{
    protected $entity = 'date';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_date_item object to an array.
     *
     * @param cs_dates_item $dateItem
     */
    public function transform($dateItem): array
    {
        $dateData = [];

        if ($dateItem) {
            $dateData['title'] = html_entity_decode($dateItem->getTitle());
            $dateData['description'] = $dateItem->getDescription();
            $dateData['permission'] = !$dateItem->isPublic();
            $dateData['place'] = $dateItem->getPlace();

            $datetimeStart = new DateTime($dateItem->getDateTime_start());
            $dateData['start']['date'] = $datetimeStart;
            $dateData['start']['time'] = $datetimeStart;

            $datetimeEnd = new DateTime($dateItem->getDateTime_end());
            $dateData['end']['date'] = $datetimeEnd;
            $dateData['end']['time'] = $datetimeEnd;

            $dateData['whole_day'] = $dateItem->isWholeDay();

            $dateData['calendar'] = $dateItem->getCalendarId();

            if ('' != $dateItem->getRecurrencePattern()) {
                $dateData = array_merge($dateData, $dateItem->getRecurrencePattern());
                $dateData['recurring_sub']['untilDate'] = new DateTime($dateData['recurringEndDate']);
            }

            if ($dateItem->isNotActivated()) {
                $dateData['hidden'] = true;

                $activating_date = $dateItem->getActivatingDate();
                if (!stristr((string) $activating_date, '9999')) {
                    $datetime = new DateTime($activating_date);
                    $dateData['hiddendate']['date'] = $datetime;
                    $dateData['hiddendate']['time'] = $datetime;
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
     * Applies an array of data to an existing object.
     *
     * @param cs_dates_item $dateObject
     * @param array          $dateData
     *
     * @return cs_dates_item
     */
    public function applyTransformation($dateObject, $dateData): cs_dates_item
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
            $dateData['start']['time'] = (new DateTime())->setTime(0, 0, 0);
            $dateData['end']['time'] = (new DateTime())->setTime(23, 59, 59);
        }

        if (!empty($dateData['start']['date'])) {
            $dateObject->setStartingDay($dateData['start']['date']->format('Y-m-d'));
            $dateObject->setStartingTime($dateData['start']['time']->format('H:i'));
            $dateObject->setDatetime_start($dateData['start']['date']->format('Y-m-d') . ' ' . $dateData['start']['time']->format('H:i:s'));
        }

        if (!empty($dateData['end']['date'])) {
            $dateObject->setEndingDay($dateData['end']['date']->format('Y-m-d'));
            $dateObject->setEndingTime($dateData['end']['time']->format('H:i'));
            $dateObject->setDatetime_end($dateData['end']['date']->format('Y-m-d') . ' ' . $dateData['end']['time']->format('H:i:s'));
        }

        $dateObject->setCalendarId($dateData['calendar']);

        if (isset($dateData['hidden'])) {
            if ($dateData['hidden']) {
                if (isset($dateData['hiddendate']['date'])) {
                    // add validdate to validdate
                    $datetime = $dateData['hiddendate']['date'];
                    if ($dateData['hiddendate']['time']) {
                        $time = explode(':', (string) $dateData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $dateObject->setActivationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $dateObject->setActivationDate('9999-00-00 00:00:00');
                }
            } else {
                if ($dateObject->isNotActivated()) {
                    $dateObject->setActivationDate(null);
                }
            }
        } else {
            if ($dateObject->isNotActivated()) {
                $dateObject->setActivationDate(null);
            }
        }

        // external viewer
        if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
            if (!empty(trim((string) $dateData['external_viewer']))) {
                $userIds = explode(' ', (string) $dateData['external_viewer']);
                $dateObject->setExternalViewerAccounts($userIds);
            } else {
                $dateObject->unsetExternalViewerAccounts();
            }
        }

        return $dateObject;
    }
}
