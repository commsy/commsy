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

namespace App\Action\Delete;

use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use App\Services\MarkedService;
use cs_dates_item;
use cs_environment;
use cs_item;
use cs_list;
use DateTime;
use Symfony\Component\Routing\RouterInterface;

class DeleteDate implements DeleteInterface
{
    private bool $recurring;

    private string $dateMode = 'normal';

    private cs_environment $legacyEnvironment;

    public function __construct(
        private RouterInterface $router,
        private MarkedService $markedService,
        private CalendarsService $calendarsService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function setRecurring(bool $recurring): void
    {
        $this->recurring = $recurring;
    }

    public function setDateMode(string $dateMode): void
    {
        $this->dateMode = $dateMode;
    }

    public function delete(cs_item $item): void
    {
        $item->delete();

        $this->markedService->removeItemFromClipboard($item->getItemId());

        /** @var cs_dates_item $date */
        $date = $item;

        $this->calendarsService->updateSynctoken($date->getCalendarId());

        $datesManager = $this->legacyEnvironment->getDatesManager();
        $datesManager->resetLimits();
        $datesManager->setRecurrenceLimit($date->getRecurrenceId());
        $datesManager->setWithoutDateModeLimit();
        $datesManager->select();

        /** @var cs_list $recurringDates */
        $recurringDates = $datesManager->get();

        if ($this->recurring && '' != $date->getRecurrenceId()) {
            $recurringDate = $recurringDates->getFirst();
            while ($recurringDate) {
                $recurringDate->delete();
                $recurringDate = $recurringDates->getNext();
            }
        } else {
            $recurringDate = $recurringDates->getFirst();
            while ($recurringDate) {
                $recurrencePattern = $recurringDate->getRecurrencePattern();
                $recurrencePatternExcludeDate = new DateTime($date->getDateTime_start());
                if (!isset($recurrencePattern['recurringExclude'])) {
                    $recurrencePattern['recurringExclude'] = [$recurrencePatternExcludeDate->format('Ymd\THis')];
                } else {
                    $recurrencePattern['recurringExclude'][] = $recurrencePatternExcludeDate->format('Ymd\THis');
                }
                $recurringDate->setRecurrencePattern($recurrencePattern);
                $recurringDate->save();

                $recurringDate = $recurringDates->getNext();
            }
        }
    }

    /**
     * @return string|null
     */
    public function getRedirectRoute(cs_item $item)
    {
        /** @var cs_dates_item $date */
        $date = $item;

        if ('normal' == $this->dateMode) {
            return $this->router->generate('app_date_list', [
                'roomId' => $date->getContextID(),
            ]);
        }

        return $this->router->generate('app_date_calendar', [
            'roomId' => $date->getContextID(),
        ]);
    }
}
