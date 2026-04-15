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

use App\Rubric\Dates\DatesDeleter;
use App\Services\LegacyEnvironment;
use App\Services\MarkedService;
use cs_dates_item;
use cs_environment;
use cs_item;
use Symfony\Component\Routing\RouterInterface;

/**
 * Thin wrapper that dispatches between single-occurrence, series and
 * "exclude from series" deletion paths. All actual deletion work (row
 * soft-delete, links/annotations cleanup, CalDAV sync-token bump, event
 * dispatch) lives in {@see DatesDeleter}.
 */
class DeleteDate implements DeleteInterface
{
    private bool $recurring = false;

    private string $dateMode = 'normal';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly MarkedService $markedService,
        private readonly DatesDeleter $datesDeleter,
        LegacyEnvironment $legacyEnvironment,
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
        /** @var cs_dates_item $date */
        $date = $item;

        $deleterId = (int) $this->legacyEnvironment->getCurrentUserItem()?->getItemID();
        $itemId = (int) $date->getItemId();
        $recurrenceId = (int) $date->getRecurrenceId();

        if ($this->recurring && $recurrenceId > 0) {
            $this->datesDeleter->deleteSeries($recurrenceId, $deleterId);
        } elseif ($recurrenceId > 0) {
            $this->datesDeleter->excludeOccurrenceFromSeries($itemId, $deleterId);
        } else {
            $this->datesDeleter->deleteItem($itemId, $deleterId);
        }

        // UI-specific side effect: drop the item from every user's clipboard.
        // Kept here rather than in the deleter because clipboard state is a
        // UI concern that does not apply to account-wide user-content cleanup.
        $this->markedService->removeItemFromClipboard($itemId);
    }

    public function getRedirectRoute(cs_item $item): ?string
    {
        /** @var cs_dates_item $date */
        $date = $item;

        if ('normal' === $this->dateMode) {
            return $this->router->generate('app_date_list', [
                'roomId' => $date->getContextID(),
            ]);
        }

        return $this->router->generate('app_date_calendar', [
            'roomId' => $date->getContextID(),
        ]);
    }
}
