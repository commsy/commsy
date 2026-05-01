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

namespace App\Rubric\Dates;

use App\Event\ItemDeletedEvent;
use App\Rubric\RubricDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_dates_item;
use DateTime;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes Dates items. Replaces the legacy `cs_dates_item::delete()` /
 * `cs_dates_manager::delete()` cascade.
 *
 * Beyond the generic RubricDeleter contract, exposes {@see deleteSeries()}
 * and {@see excludeOccurrenceFromSeries()} for the UI `DeleteDate` action.
 */
class DatesDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CalendarsService $calendarsService,
        private readonly LegacyEnvironment $legacyEnvironment,
    ) {}

    public function rubricType(): RubricType
    {
        return RubricType::Date;
    }

    public function findItemIdsCreatedBy(int $userId, int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM dates
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    public function findItemIdsInContext(int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM dates
                WHERE context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    /**
     * Soft-deletes a single date occurrence. Use {@see deleteSeries()} to
     * remove every occurrence, or {@see excludeOccurrenceFromSeries()} to
     * keep siblings but suppress this one in the RRULE.
     */
    public function softDeleteItem(int $itemId, int $deleterId): void
    {
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        $this->connection->executeStatement(
            'UPDATE dates
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        $this->rubricDeletionHelper->softDeleteLinks($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteAnnotations($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteFileLinks($itemId);
        $this->rubricDeletionHelper->softDeleteItemsRow($itemId, $deleterId);

        // Bump the CalDAV sync token so external clients notice the change.
        if ($typedItem instanceof cs_dates_item) {
            $calendarId = $typedItem->getCalendarId();
            if (!empty($calendarId)) {
                $this->calendarsService->updateSynctoken($calendarId);
            }
        }
    }

    /**
     * Soft-deletes every occurrence that shares the given recurrence id.
     */
    public function deleteSeries(int $recurrenceId, int $deleterId): void
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM dates
                WHERE recurrence_id = :recurrenceId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['recurrenceId' => $recurrenceId]
        );

        foreach ($itemIds as $itemId) {
            $this->softDeleteItem((int) $itemId, $deleterId);
        }
    }

    /**
     * Removes a single occurrence from an otherwise-intact series. The
     * occurrence is soft-deleted; each sibling's recurrence_pattern is
     * patched with a `recurringExclude` entry so CalDAV/RRULE consumers
     * skip the slot.
     */
    public function excludeOccurrenceFromSeries(int $itemId, int $deleterId): void
    {
        $typedItem = $this->itemService->getTypedItem($itemId);
        if (!$typedItem instanceof cs_dates_item) {
            return;
        }

        $recurrenceId = (int) $typedItem->getRecurrenceId();
        if ($recurrenceId <= 0) {
            // Not part of a series — fall back to a plain delete.
            $this->softDeleteItem($itemId, $deleterId);
            return;
        }

        $excludeDate = new DateTime($typedItem->getDateTime_start());
        $excludeToken = $excludeDate->format('Ymd\THis');

        // Reuse legacy manager accessors since recurrence_pattern is stored
        // serialized.
        $datesManager = $this->legacyEnvironment->getEnvironment()->getDatesManager();
        $datesManager->resetLimits();
        $datesManager->setRecurrenceLimit((string) $recurrenceId);
        $datesManager->setWithoutDateModeLimit();
        $datesManager->select();
        $list = $datesManager->get();

        $sibling = $list->getFirst();
        while ($sibling) {
            if ($sibling->getItemId() !== $itemId) {
                $pattern = $sibling->getRecurrencePattern();
                if (!isset($pattern['recurringExclude'])) {
                    $pattern['recurringExclude'] = [$excludeToken];
                } else {
                    $pattern['recurringExclude'][] = $excludeToken;
                }
                $sibling->setRecurrencePattern($pattern);
                $sibling->save();
            }
            $sibling = $list->getNext();
        }

        $this->softDeleteItem($itemId, $deleterId);
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE dates SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE dates SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }

    public function hardDeleteOlderThan(int $days): int
    {
        return (int) $this->connection->executeStatement(
            'DELETE FROM dates
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }
}
