<?php

namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormatDateTimeRangeExtension extends AbstractExtension
{
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    private $translator;

    public function __construct(LegacyEnvironment $legacyEnvironment, TranslatorInterface $translator)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('formatDateTimeRange', [$this, 'formatDateTimeRange']),
        ];
    }

    public function formatDateTimeRange(bool $wholeDay, \DateTime $dateTimeStart, ?\DateTime $dateTimeEnd)
    {
        // define the format for the generated date & time strings
        $dateFormat = 'd.m.Y';
        $timeFormat = 'H:i';

        $locale = $this->legacyEnvironment->getSelectedLanguage();
        if ($locale === 'en') {
            $dateFormat = 'm/d/Y';
            $timeFormat = 'h:i a';
        }

        // generate date & time strings
        $formattedDateStart = $dateTimeStart->format($dateFormat);
        $formattedTimeStart = $dateTimeStart->format($timeFormat);

        $formattedDateEnd = isset($dateTimeEnd) ? $dateTimeEnd->format($dateFormat) : $formattedDateStart;
        $formattedTimeEnd = isset($dateTimeEnd) ? $dateTimeEnd->format($timeFormat) : $formattedTimeStart;

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
