<?php

namespace App\Utils;

use App\Entity\Portal;
use App\Model\TimePulseTemplate;
use App\Services\LegacyEnvironment;

/**
 * Implements services for time pulses and time pulse templates
 *
 * A time pulse template describes a division of a year (like a semester or trimester)
 */
class TimePulsesService
{
    /**
     * @var LegacyEnvironment $legacyEnvironment
     */
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    /**
     * Returns all time pulse templates defined for the given portal as an array of TimePulseTemplate
     * data objects
     * @param Portal $portal the portal for which time pulse templates shall be returned
     * @return TimePulseTemplate[] array of TimePulseTemplate data objects
     */
    public function getTimePulseTemplates(Portal $portal): array
    {
        $rawTimePulseTemplates = $portal->getTimeTextArray();
        $templateIds = array_keys($rawTimePulseTemplates);

        $timePulseTemplates = [];
        foreach ($templateIds as $templateId) {
            $timePulseTemplate = $this->getTimePulseTemplate($portal, $templateId);
            if ($timePulseTemplate) {
                $timePulseTemplates[] = $timePulseTemplate;
            }
        }

        // sort all time pulse templates first by start month & day, then by end month & day
        uasort($timePulseTemplates, array("App\Model\TimePulseTemplate", "compare"));

        return $timePulseTemplates;
    }

    /**
     * For the given portal, returns a data object representing the time pulse template with the given ID
     * @param Portal $portal the portal which contains the time pulse template
     * @param int $templateId the ID of the time pulse template that shall be returned
     * @return TimePulseTemplate|null a data object representing the time pulse template, or null if no
     * time pulse template with the given ID could be found, or if an error occurred
     */
    public function getTimePulseTemplate(Portal $portal, int $templateId): ?TimePulseTemplate
    {
        // NOTE: raw data for all defined time pulse templates are stored as an
        // array (with extras key `TIME_TEXT_ARRAY`) in the `portal` database table;
        // in that array, we assume the array index of a template item to represent its ID;
        // Example array with two time pulse templates (as encoded in the `extras` column):
        // s:15:\"TIME_TEXT_ARRAY\";a:2:{i:1;a:4:{s:2:\"DE\";s:17:\"Sommersemester %1\";s:2:\"EN\";s:14:\"summer term %1\";s:5:\"BEGIN\";s:5:\"01.01\";s:3:\"END\";s:5:\"30.05\";}i:2;a:4:{s:2:\"DE\";s:17:\"Wintersemester %1\";s:2:\"EN\";s:14:\"winter term %1\";s:5:\"BEGIN\";s:5:\"01.06\";s:3:\"END\";s:5:\"31.12\";}}
        $rawTimePulseTemplates = $portal->getTimeTextArray();

        $rawTimePulseTemplate = $rawTimePulseTemplates[$templateId] ?? null;
        if (empty($rawTimePulseTemplate)) {
            return null;
        }

        // map raw time pulse template data to our data object properties
        $timePulseTemplate = new TimePulseTemplate();
        $timePulseTemplate->setId($templateId);
        $timePulseTemplate->setContextId($portal->getId());
        $timePulseTemplate->setTitleGerman($rawTimePulseTemplate['DE']);
        $timePulseTemplate->setTitleEnglish($rawTimePulseTemplate['EN']);

        // expected format for the raw BEGIN/END values: <DAYNUMBER>.<MONTHNUMBER> (like "01.01" or "31.12")
        $startParts = explode(".", $rawTimePulseTemplate['BEGIN']);
        $endParts = explode(".", $rawTimePulseTemplate['END']);
        if (count($startParts) !== 2 || count($endParts) !== 2) {
            return null;
        }

        $startDay = intval($startParts[0]);
        $startMonth = intval($startParts[1]);
        $endDay = intval($endParts[0]);
        $endMonth = intval($endParts[1]);
        if (empty($startDay) || empty($startMonth) || empty($endDay) || empty($endMonth)) {
            return null;
        }

        $timePulseTemplate->setStartDay($startDay);
        $timePulseTemplate->setStartMonth($startMonth);
        $timePulseTemplate->setEndDay($endDay);
        $timePulseTemplate->setEndMonth($endMonth);

        return $timePulseTemplate;
    }

    /**
     * Updates existing raw data for the given time pulse template in the given portal, or else adds it
     * @param Portal $portal the portal hosting the given time pulse template
     * @param TimePulseTemplate $timePulseTemplate the time pulse template that shall be updated
     */
    public function updateTimePulseTemplate(Portal $portal, TimePulseTemplate $timePulseTemplate): void
    {
        $rawTimePulseTemplates = $portal->getTimeTextArray();

        $rawTimePulseTemplate = [
            'DE' => $timePulseTemplate->getTitleGerman(),
            'EN' => $timePulseTemplate->getTitleEnglish(),
            'BEGIN' => sprintf('%02d.%02d', strval($timePulseTemplate->getStartDay()), strval($timePulseTemplate->getStartMonth())),
            'END' => sprintf('%02d.%02d', strval($timePulseTemplate->getEndDay()), strval($timePulseTemplate->getEndMonth())),
        ];

        $templateId = $timePulseTemplate->getId();
        if (!empty($templateId)) {
            $rawTimePulseTemplates[$templateId] = $rawTimePulseTemplate;
        } else {
            $rawTimePulseTemplates[] = $rawTimePulseTemplate;
        }
        $portal->setTimeTextArray($rawTimePulseTemplates);
    }

    /**
     * Removes the raw data for the time pulse template with the given ID in the given portal
     * @param Portal $portal the portal which contains the time pulse template
     * @param int $templateId the ID of the time pulse template that shall be removed
     */
    public function removeTimePulseTemplate(Portal $portal, int $templateId): void
    {
        $rawTimePulseTemplates = $portal->getTimeTextArray();
        unset($rawTimePulseTemplates[$templateId]);
        $portal->setTimeTextArray($rawTimePulseTemplates);
    }

    /**
     * Takes the given portal's time pulse settings & templates, and inserts/removes corresponding cs_label_item
     * objects in the database that represent the concrete time pulses for these time pulse settings & templates
     * @param Portal $portal the portal hosting the time pulses to be updated
     */
    public function updateTimePulseLabels(Portal $portal): void
    {
        $timePulseTemplates = $this->getTimePulseTemplates($portal);

        $current_pos = 0;

        // TODO: the below legacy code (originally extracted from `configuration_time.php`) should get rewritten!

        // change (insert) time labels
        $clock_pulse_array = array();
        if (!empty($timePulseTemplates)) {
            $current_year = date('Y');
            $current_date = getCurrentDate();
            $ad_year = 0;
            $first = true;
            foreach ($timePulseTemplates as $key => $timePulseTemplate) {
                $begin = sprintf('%02d.%02d', strval($timePulseTemplate->getStartMonth()), strval($timePulseTemplate->getStartDay()));
                $end = sprintf('%02d.%02d', strval($timePulseTemplate->getEndMonth()), strval($timePulseTemplate->getEndDay()));

                $begin2 = ($current_year+$ad_year).$begin;
                if ($end < $begin) {
                    $ad_year++;
                }
                $end2 = ($current_year+$ad_year).$end;

                if ($first) {
                    $first = false;
                    $begin_first = $begin2;
                }

                if ( $begin2 <= $current_date
                    and $current_date <= $end2) {
                    $current_pos = $key;
                }
            }

            $year = $current_year;

            if ($current_date < $begin_first) {
                $year--;
                $current_pos = count($timePulseTemplates);
            }

            $count = count($timePulseTemplates);
            $position = 1;
            for ($i=0; $i < $portal->getNumberOfFutureTimePulses() + $current_pos; $i++) {
                $clock_pulse_array[] = $year.'_'.$position;
                $position++;
                if ($position > $count) {
                    $position = 1;
                    $year++;
                }
            }
        }

        $currentUserItem = $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();
        $time_manager = $this->legacyEnvironment->getEnvironment()->getTimeManager();

        if (!empty($clock_pulse_array)) {
            $done_array = array();
            $time_manager->reset();
            $time_manager->setContextLimit($portal->getId());
            $time_manager->setDeleteLimit(false);
            $time_manager->select();
            $time_list = $time_manager->get();
            if ($time_list->isNotEmpty()) {
                $time_label = $time_list->getFirst();
                while ($time_label) {
                    if (!in_array($time_label->getTitle(),$clock_pulse_array)) {
                        $first_new_clock_pulse = $clock_pulse_array[0];
                        $last_new_clock_pulse = array_pop($clock_pulse_array);
                        $clock_pulse_array[] = $last_new_clock_pulse;
                        if ($time_label->getTitle() < $first_new_clock_pulse) {
                            $temp_clock_pulse_array = explode('_',$time_label->getTitle());
                            $clock_pulse_pos = $temp_clock_pulse_array[1];
                            if ($clock_pulse_pos > $count) {
                                if (!$time_label->isDeleted()) {
                                    $time_label->setDeleterItem($currentUserItem);
                                    $time_label->delete();
                                }
                            } else {
                                if ($time_label->isDeleted()) {
                                    $time_label->setModificatorItem($currentUserItem);
                                    $time_label->unDelete();
                                }
                            }
                        } elseif ($time_label->getTitle() > $last_new_clock_pulse) {
                            if (!$time_label->isDeleted()) {
                                $time_label->setDeleterItem($currentUserItem);
                                $time_label->delete();
                            }
                        } else {
                            if (!$time_label->isDeleted()) {
                                $time_label->setDeleterItem($currentUserItem);
                                $time_label->delete();
                            }
                        }
                    } else {
                        if ($time_label->isDeleted()) {
                            $time_label->setModificatorItem($currentUserItem);
                            $time_label->unDelete();
                        }
                        $done_array[] = $time_label->getTitle();
                    }
                    $time_label = $time_list->getNext();
                }
            }

            foreach ($clock_pulse_array as $clock_pulse) {
                if (!in_array($clock_pulse,$done_array)) {
                    $time_label = $time_manager->getNewItem();
                    $time_label->setContextID($portal->getId());
                    $user = $currentUserItem;
                    $time_label->setCreatorItem($user);
                    $time_label->setModificatorItem($user);
                    $time_label->setTitle($clock_pulse);
                    $time_label->save();
                }
            }
        } else {
            $time_manager->reset();
            $time_manager->setContextLimit($portal->getId());
            $time_manager->select();
            $time_list = $time_manager->get();
            if ($time_list->isNotEmpty()) {
                $time_label = $time_list->getFirst();
                while ($time_label) {
                    $time_label->setDeleterItem($currentUserItem);
                    $time_label->delete();
                    $time_label = $time_list->getNext();
                }
            }
        }

        // renew links to continuous rooms
        $room_list = $portal->getContinuousRoomList($this->legacyEnvironment);
        if ($room_list->isNotEmpty()) {
            $room_item2 = $room_list->getFirst();
            while ($room_item2) {
                if ($room_item2->isOpen()) {
                    $room_item2->setContinuous();
                    $room_item2->saveWithoutChangingModificationInformation();
                }
                $room_item2 = $room_list->getNext();
            }
        }
    }
}
