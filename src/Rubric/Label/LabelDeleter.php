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

namespace App\Rubric\Label;

use App\Files\FileDeleter;
use App\Event\ItemDeletedEvent;
use App\Rubric\RubricDeletionHelper;
use App\Rubric\RubricDeleter;
use App\Rubric\RubricType;
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes items of the complete `cs_label_item` hierarchy (topic / hashtag /
 * buzzword / timepulse / institution / group). Replaces the legacy
 * `cs_label_item::delete()` / `cs_labels_manager::delete()` cascade.
 *
 * Grouproom cascade on `cs_group_item` deletion is not reproduced — all
 * current call sites handle the grouproom out-of-band.
 */
class LabelDeleter implements RubricDeleter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ItemService $itemService,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly FileDeleter $fileDeleter,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rubricType(): RubricType
    {
        return RubricType::Label;
    }

    /**
     * None: labels are the shared vocabulary and structure of the room, not
     * one person's entries, so deleting a person's entries keeps them;
     * {@see LabelRedactor} redacts what the person wrote in them.
     */
    public function findItemIdsCreatedBy(int $userId, int $contextId): array
    {
        return [];
    }

    public function findItemIdsInContext(int $contextId): array
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM labels
                WHERE context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['contextId' => $contextId]
        );

        return array_map('intval', $itemIds);
    }

    public function softDeleteItem(int $itemId, int $deleterId): void
    {
        $typedItem = $this->itemService->getTypedItem($itemId);
        if ($typedItem !== null) {
            $this->eventDispatcher->dispatch(new ItemDeletedEvent($typedItem), ItemDeletedEvent::NAME);
        }

        // Single labels table covers every subtype (stored in labels.type).
        $this->connection->executeStatement(
            'UPDATE labels
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE item_id = :itemId',
            ['deleterId' => $deleterId, 'itemId' => $itemId]
        );

        $this->rubricDeletionHelper->softDeleteLinks($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteLinkItems($itemId, $deleterId);
        $this->fileDeleter->detachFromItem($itemId, $deleterId);
        $this->rubricDeletionHelper->softDeleteItemsRow($itemId, $deleterId);
    }

    /**
     * Left to {@see LabelRedactor}, which redacts the person's labels first
     * and needs their authorship for it.
     */
    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
    }

    public function hardDeleteOlderThan(int $days): int
    {
        return (int) $this->connection->executeStatement(
            'DELETE FROM labels
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
    }
}
