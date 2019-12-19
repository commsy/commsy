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

    public function formatDateTimeRange(bool $wholeDay, \DateTime $dateTimeStart, ?\DateTime $dateTimeEnd)
    {
        global $symfonyContainer;
        $locale = $this->legacyEnvironment->getSelectedLanguage();

        // define the format for the generated date & time strings
        $dateFormatType = $symfonyContainer->getParameter('craue_twig_extensions.formatDateTime.datetype'); // "none", "full", "long", "medium", or "short"
        $timeFormatType = $symfonyContainer->getParameter('craue_twig_extensions.formatDateTime.timetype'); // "full", "long", "medium", or "short"

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
