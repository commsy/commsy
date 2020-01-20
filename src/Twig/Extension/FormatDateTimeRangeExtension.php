<?php

namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use Craue\TwigExtensionsBundle\Twig\Extension as Craue;

class FormatDateTimeRangeExtension extends AbstractExtension
{
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    private $translator;

    private $dateTimeFormatter;

    public function __construct(LegacyEnvironment $legacyEnvironment, TranslatorInterface $translator, Craue\FormatDateTimeExtension $dateTimeFormatter)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('formatDateTimeRange', [$this, 'formatDateTimeRange']),
        ];
    }

    /**
     * Returns a formatted string which describes the given date & time range according to the user's currently chosen
     * language.
     * Example:
     * DE: "am 26.02.2019 von 12:00 Uhr bis 13:00 Uhr", extended: "am Mittwoch, 26. Februar 2020 von 12:00 Uhr bis 13:00 Uhr"
     * EN: "on Feb 26, 2019 from 12:00 PM till 1:00 PM", extended: "on Wednesday, February 26, 2020 from 12:00 PM till 1:00 PM"
     *
     * @param boolean $wholeDay whether the given date(s) describe a whole day event (true) or not (false)
     * @param \DateTime $dateTimeStart the start date of the date & time range
     * @param \DateTime|null $dateTimeEnd the end date of the date & time range; may be null in which case the start
     * date will be also used as the end date
     * @param boolean $extendedFormat whether the returned string shall be formatted as a more detailed date & time
     * range description (true) or not (false); defaults to false
     *
     * @return string formatted date & time range description
     */
    public function formatDateTimeRange(bool $wholeDay, \DateTime $dateTimeStart, ?\DateTime $dateTimeEnd, bool $extendedFormat = false)
    {
        global $symfonyContainer;
        $locale = $this->legacyEnvironment->getSelectedLanguage();

        // define the format for the generated date & time strings
        $dateFormatType = $symfonyContainer->getParameter('craue_twig_extensions.formatDateTime.datetype'); // "none", "full", "long", "medium", or "short"
        $timeFormatType = $symfonyContainer->getParameter('craue_twig_extensions.formatDateTime.timetype'); // "full", "long", "medium", or "short"

        if ($extendedFormat === true) {
            $dateFormatType = "full";
        }

        // generate date & time strings
        $formattedDateStart = $this->dateTimeFormatter->formatDate($dateTimeStart, $locale, $dateFormatType);
        $formattedTimeStart = $this->dateTimeFormatter->formatTime($dateTimeStart, $locale, $timeFormatType);

        $formattedDateEnd = isset($dateTimeEnd) ? $this->dateTimeFormatter->formatDate($dateTimeEnd, $locale, $dateFormatType) : $formattedDateStart;
        $formattedTimeEnd = isset($dateTimeEnd) ? $this->dateTimeFormatter->formatTime($dateTimeEnd, $locale, $timeFormatType) : $formattedTimeStart;

        // generate composite strings
        if ($formattedDateStart === $formattedDateEnd) {
            if ($wholeDay) {
                $formatted = $this->translator->trans('short date description', [
                    "%date%" => $formattedDateStart,
                ], 'date');
            } elseif ($formattedTimeStart === $formattedTimeEnd) {
                $formatted = $this->translator->trans('short date and time description', [
                    "%date%" => $formattedDateStart,
                    "%time%" => $formattedTimeStart,
                ], 'date');
            } else {
                $formatted = $this->translator->trans('short date and time range description', [
                    "%date%" => $formattedDateStart,
                    "%timeStart%" => $formattedTimeStart,
                    "%timeEnd%" => $formattedTimeEnd,
                ], 'date');
            }
        } else {
            if ($wholeDay) {
                $formatted = $this->translator->trans('short date range description', [
                    "%dateStart%" => $formattedDateStart,
                    "%dateEnd%" => $formattedDateEnd,
                ], 'date');
            } else {
                $formatted = $this->translator->trans('short date range and time range description', [
                    "%dateStart%" => $formattedDateStart,
                    "%timeStart%" => $formattedTimeStart,
                    "%dateEnd%" => $formattedDateEnd,
                    "%timeEnd%" => $formattedTimeEnd,
                ], 'date');
            }

            // add day range info
            $formatted .= ' ('
                . $this->translator->trans('number of days description', [
                    "%numberOfDays%" => $this->daysTouchedByDateRange($dateTimeStart, $dateTimeEnd),
                ], 'date')
                . ')';
        }

        return $formatted;
    }

    /**
     * Returns the number of days touched by the specified date range.
     * Note that this method won't calculate the exact time difference between two DateTime objects measured in days
     * but instead determines the total number of days between the given start and end dates (inclusively).
     *
     * @param \DateTime $startDate The first day of the date range whose total number of days shall be calculated.
     * @param \DateTime $endDate The last day of the date range whose total number of days shall be calculated.
     * @return float
     */
    public function daysTouchedByDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        $startDate->setTime(0, 0, 0);
        $endDate->setTime(23, 59, 59);
        $secondsDiff = $endDate->getTimestamp() - $startDate->getTimestamp();
        $daysTouched = ceil(round($secondsDiff/60/60/24, 1));

        return $daysTouched;
    }
}
