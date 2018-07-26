<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 16.07.18
 * Time: 19:34
 */

namespace CommsyBundle\Action\Delete;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommsyBundle\Services\CalendarsService;
use Symfony\Component\Routing\RouterInterface;

class DeleteDate extends DeleteGeneric
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var bool
     */
    private $recurring;

    /**
     * @var string
     */
    private $dateMode = 'normal';

    /**
     * @var CalendarsService
     */
    private $calendarsService;

    public function __construct(RouterInterface $router, LegacyEnvironment $legacyEnvironment, CalendarsService $calendarsService)
    {
        parent::__construct($legacyEnvironment);

        $this->router = $router;
        $this->calendarsService = $calendarsService;
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
     * @param \cs_item $item
     */
    public function delete(\cs_item $item): void
    {
        parent::delete($item);

        /** @var \cs_dates_item $date */
        $date = $item;

        $this->calendarsService->updateSynctoken($date->getCalendarId());

        $datesManager = $this->legacyEnvironment->getDatesManager();
        $datesManager->resetLimits();
        $datesManager->setRecurrenceLimit($date->getRecurrenceId());
        $datesManager->setWithoutDateModeLimit();
        $datesManager->select();

        /** @var \cs_list $recurringDates */
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
                $recurrencePatternExcludeDate = new \DateTime($item->getDateTime_start());
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
     * @param \cs_item $item
     * @return string|null
     */
    public function getRedirectRoute(\cs_item $item)
    {
        /** @var \cs_dates_item $date */
        $date = $item;

        if ($this->dateMode == 'normal') {
            return $this->router->generate('commsy_date_list', [
                'roomId' => $date->getContextID(),
            ]);
        }

        return $this->router->generate('commsy_date_calendar', [
            'roomId' => $date->getContextID(),
        ]);
    }
}