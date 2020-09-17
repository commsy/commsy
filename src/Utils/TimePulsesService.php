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
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
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
}
