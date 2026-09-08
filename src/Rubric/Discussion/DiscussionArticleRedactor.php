<?php

namespace App\Rubric\Discussion;

use App\Event\ItemReindexEvent;
use App\Rubric\RedactionText;
use App\Rubric\RubricDeletionHelper;
use App\Rubric\SubEntryRedactor;
use App\Utils\ItemService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DiscussionArticleRedactor implements SubEntryRedactor
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RedactionText $redactionText,
        private readonly RubricDeletionHelper $rubricDeletionHelper,
        private readonly ItemService $itemService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Articles have no title column — the thread shows the description.
     * The row and its `position` stay so the thread tree holds together.
     */
    public function redactContentOfUser(int $userId, int $contextId): void
    {
        $articleIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT item_id FROM discussionarticles
                WHERE creator_id = :userId
                  AND context_id = :contextId
                  AND deleter_id IS NULL
                  AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        ));
        if ($articleIds === []) {
            return;
        }

        $discussionIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT DISTINCT discussion_id FROM discussionarticles WHERE item_id IN (:ids)',
            ['ids' => $articleIds],
            ['ids' => ArrayParameterType::INTEGER]
        ));

        $this->connection->executeStatement(
            'UPDATE discussionarticles
                SET description = :description, modification_date = NOW()
                WHERE item_id IN (:ids)',
            [
                'description' => $this->redactionText->description($contextId),
                'ids' => $articleIds,
            ],
            ['ids' => ArrayParameterType::INTEGER]
        );

        foreach ($articleIds as $articleId) {
            $this->rubricDeletionHelper->softDeleteFileLinks($articleId, $userId);
        }

        // Articles have no index of their own — their text lives in the
        // parent discussion's document.
        foreach ($discussionIds as $discussionId) {
            $discussion = $this->itemService->getTypedItem($discussionId);
            if ($discussion !== null) {
                $this->eventDispatcher->dispatch(new ItemReindexEvent($discussion), ItemReindexEvent::class);
            }
        }
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE discussionarticles SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE discussionarticles SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }
}
