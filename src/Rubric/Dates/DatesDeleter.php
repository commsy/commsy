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
use App\Rubric\ItemDeletionHelper;
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
 * Deletes Dates items without delegating to the legacy
 * `cs_dates_item::delete()` / `cs_dates_manager::delete()` cascade.
 *
 * In addition to the generic RubricDeleter contract (single item delete), this
 * class exposes two Dates-specific operations that the UI `DeleteDate` action
 * needs — series deletion and "exclude occurrence from series" — so the action
 * can stay a thin wrapper around the deleter.
 */
class DatesDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly ItemDeletionHelper $itemDeletionHelper,
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
     * Soft-deletes a single date occurrence (series membership is not
     * considered here — use {@see deleteSeries()} to remove every occurrence
     * of a recurrence, or {@see excludeOccurrenceFromSeries()} to keep
     * siblings but suppress this one in the RRULE).
     */
    public function deleteItem(int $itemId, int $deleterId): void
    {
        // 1. Dispatch the deletion event so ES/mail/etherpad subscribers fire.
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // 2. Soft-delete the rubric-specific `dates` row.
        $this->connection->executeStatement(
            'UPDATE dates
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        // 3. Soft-delete every `links` row referencing this date (all types,
        //    both directions). Matches the legacy cs_dates_manager behaviour
        //    which did the same via deleteLinksBecauseItemIsDeleted().
        $this->itemDeletionHelper->softDeleteLinks($itemId, $deleterId);

        // 4. Soft-delete `link_items` rows referencing this date.
        $this->itemDeletionHelper->softDeleteLinkItems($itemId, $deleterId);

        // 5. Soft-delete annotations attached to this date.
        $this->itemDeletionHelper->softDeleteAnnotations($itemId, $deleterId);

        // 6. Soft-delete file-link attachments.
        $this->itemDeletionHelper->softDeleteFileLinks($itemId);

        // 7. Soft-delete the shared `items` row — single source of truth for
        //    what it means to delete a date, both from UI and user-footprint.
        $this->itemDeletionHelper->softDeleteItemsRow($itemId, $deleterId);

        // 8. Bump the CalDAV sync token so external clients notice the change.
        //    Mirrors legacy DeleteDate::delete() which called this once per
        //    deletion. Typed item may be null if the row was already gone.
        if ($typedItem instanceof cs_dates_item) {
            $calendarId = $typedItem->getCalendarId();
            if (!empty($calendarId)) {
                $this->calendarsService->updateSynctoken($calendarId);
            }
        }
    }

    /**
     * Soft-deletes every occurrence that shares the given recurrence id.
     *
     * Mirrors the `$recurring === true` branch in legacy DeleteDate::delete().
     * updateSynctoken() is invoked per occurrence (via {@see deleteItem()}),
     * matching legacy behaviour — the CalDAV sync cost stays the same.
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
            $this->deleteItem((int) $itemId, $deleterId);
        }
    }

    /**
     * Removes a single occurrence from an otherwise-intact series.
     *
     * The occurrence itself is soft-deleted as usual; additionally, each
     * remaining sibling's recurrence_pattern is patched with a
     * `recurringExclude` entry so CalDAV/RRULE consumers skip the slot.
     *
     * Mirrors the `else` branch in legacy DeleteDate::delete().
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
            $this->deleteItem($itemId, $deleterId);
            return;
        }

        $excludeDate = new DateTime($typedItem->getDateTime_start());
        $excludeToken = $excludeDate->format('Ymd\THis');

        // Patch siblings via the legacy manager — recurrence_pattern is stored
        // serialized, so we reuse its get/set accessors rather than hand-rolling
        // the serialization here.
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

        // Finally soft-delete the occurrence itself.
        $this->deleteItem($itemId, $deleterId);
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
}
