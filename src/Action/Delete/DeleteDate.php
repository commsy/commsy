<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 16.07.18
 * Time: 19:34
 */

namespace App\Action\Delete;


use App\Services\CalendarsService;
use App\Services\CopyService;
use App\Services\LegacyEnvironment;
use cs_dates_item;
use cs_environment;
use cs_item;
use cs_list;
use DateTime;
use Symfony\Component\Routing\RouterInterface;

class DeleteDate implements DeleteInterface
{
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var bool
     */
    private bool $recurring;

    /**
     * @var string
     */
    private string $dateMode = 'normal';

    /**
     * @var CopyService
     */
    private CopyService $copyService;

    /**
     * @var CalendarsService
     */
    private CalendarsService $calendarsService;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(
        RouterInterface $router,
        CopyService $copyService,
        CalendarsService $calendarsService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->router = $router;
        $this->copyService = $copyService;
        $this->calendarsService = $calendarsService;
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

    /**
     * @param cs_item $item
     */
    public function delete(cs_item $item): void
    {
        $item->delete();

        $this->copyService->removeItemFromClipboard($item->getItemId());

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

        if ($this->recurring && $date->getRecurrenceId() != '') {
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
     * @param cs_item $item
     * @return string|null
     */
    public function getRedirectRoute(cs_item $item)
    {
        /** @var cs_dates_item $date */
        $date = $item;

        if ($this->dateMode == 'normal') {
            return $this->router->generate('app_date_list', [
                'roomId' => $date->getContextID(),
            ]);
        }

        return $this->router->generate('app_date_calendar', [
            'roomId' => $date->getContextID(),
        ]);
    }
}