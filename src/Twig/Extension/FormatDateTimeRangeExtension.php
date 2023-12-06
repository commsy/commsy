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

namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use cs_environment;
use DateTime;
use IntlDateFormatter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormatDateTimeRangeExtension extends AbstractExtension
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly TranslatorInterface $translator,
        private readonly ParameterBagInterface $parameterBag
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('formatDateTimeRange', $this->formatDateTimeRange(...)),
        ];
    }

    /**
     * Returns a formatted string which describes the given date & time range according to the user's currently chosen
     * language.
     * Example:
     * DE: "am 26.02.2019 von 12:00 Uhr bis 13:00 Uhr", extended: "am Mittwoch, 26. Februar 2020 von 12:00 Uhr bis 13:00 Uhr"
     * EN: "on Feb 26, 2019 from 12:00 PM till 1:00 PM", extended: "on Wednesday, February 26, 2020 from 12:00 PM till 1:00 PM".
     *
     * @param bool $wholeDay                whether the given date(s) describe a whole day event (true) or not (false)
     * @param DateTime $dateTimeStart       the start date of the date & time range
     * @param DateTime|null $dateTimeEnd    the end date of the date & time range; may be null in which case the start
     *                                      date will be also used as the end date
     * @param bool $extendedFormat          whether the returned string shall be formatted as a more detailed date & time
     *                                      range description (true) or not (false); defaults to false
     *
     * @return string formatted date & time range description
     */
    public function formatDateTimeRange(
        bool $wholeDay,
        DateTime $dateTimeStart,
        ?DateTime $dateTimeEnd,
        bool $extendedFormat = false
    ): string
    {
        // define the format for the generated date & time strings
        $intlDateFormat = $extendedFormat ? IntlDateFormatter::FULL : IntlDateFormatter::MEDIUM;
        $intlTimeFormat = IntlDateFormatter::SHORT;

        $locale = $this->legacyEnvironment->getSelectedLanguage();
        $timezone = $this->parameterBag->get('commsy.dates.timezone');

        // generate date & time strings
        $dateFormatter = new IntlDateFormatter($locale, $intlDateFormat, IntlDateFormatter::NONE, $timezone);
        $timeFormatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, $intlTimeFormat, $timezone);

        $formattedDateStart = $dateFormatter->format($dateTimeStart);
        $formattedTimeStart = $timeFormatter->format($dateTimeStart);

        $formattedDateEnd = isset($dateTimeEnd) ? $dateFormatter->format($dateTimeEnd) : $formattedDateStart;
        $formattedTimeEnd = isset($dateTimeEnd) ? $timeFormatter->format($dateTimeEnd) : $formattedTimeStart;

        // generate composite strings
        if ($formattedDateStart === $formattedDateEnd) {
            if ($wholeDay) {
                $formatted = $this->translator->trans('short date description', [
                    '%date%' => $formattedDateStart,
                ], 'date');
            } elseif ($formattedTimeStart === $formattedTimeEnd) {
                $formatted = $this->translator->trans('short date and time description', [
                    '%date%' => $formattedDateStart,
                    '%time%' => $formattedTimeStart,
                ], 'date');
            } else {
                $formatted = $this->translator->trans('short date and time range description', [
                    '%date%' => $formattedDateStart,
                    '%timeStart%' => $formattedTimeStart,
                    '%timeEnd%' => $formattedTimeEnd,
                ], 'date');
            }
        } else {
            if ($wholeDay) {
                $formatted = $this->translator->trans('short date range description', [
                    '%dateStart%' => $formattedDateStart,
                    '%dateEnd%' => $formattedDateEnd,
                ], 'date');
            } else {
                $formatted = $this->translator->trans('short date range and time range description', [
                    '%dateStart%' => $formattedDateStart,
                    '%timeStart%' => $formattedTimeStart,
                    '%dateEnd%' => $formattedDateEnd,
                    '%timeEnd%' => $formattedTimeEnd,
                ], 'date');
            }

            // add day range info
            $formatted .= ' ('
                .$this->translator->trans('number of days description', [
                    '%numberOfDays%' => $this->daysTouchedByDateRange($dateTimeStart, $dateTimeEnd),
                ], 'date')
                .')';
        }

        return $formatted;
    }

    /**
     * Returns the number of days touched by the specified date range.
     * Note that this method won't calculate the exact time difference between two DateTime objects measured in days
     * but instead determines the total number of days between the given start and end dates (inclusively).
     *
     * @param DateTime $startDate the first day of the date range whose total number of days shall be calculated
     * @param DateTime $endDate   the last day of the date range whose total number of days shall be calculated
     *
     * @return float
     */
    public function daysTouchedByDateRange(DateTime $startDate, DateTime $endDate): float
    {
        $startDate->setTime(0, 0, 0);
        $endDate->setTime(23, 59, 59);
        $secondsDiff = $endDate->getTimestamp() - $startDate->getTimestamp();
        return ceil(round($secondsDiff / 60 / 60 / 24, 1));
    }
}
