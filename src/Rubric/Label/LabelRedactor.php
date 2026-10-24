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

declare(strict_types=1);

namespace App\Rubric\Label;

use App\Files\FileDeleter;
use App\Rubric\RedactionText;
use App\Rubric\SubEntryRedactor;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Labels outlive the person who created them: they are the shared
 * vocabulary and structure of the room. Deleting a person's entries
 * replaces only what they wrote about a topic or a group, its description,
 * and keeps the name; hashtags have nothing of that kind, the group of all
 * members is left alone.
 */
final readonly class LabelRedactor implements SubEntryRedactor
{
    private const array REDACTED_TYPES = [
        LabelType::Topic->value,
        LabelType::Group->value,
        LabelType::Institution->value,
    ];

    public function __construct(
        private Connection $connection,
        private RedactionText $redactionText,
        private FileDeleter $fileDeleter,
    ) {
    }

    public function redactContentOfUser(int $userId, int $contextId): void
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('item_id', 'extras')
            ->from('labels')
            ->where('creator_id = :userId')
            ->andWhere('context_id = :contextId')
            ->andWhere('type IN (:types)')
            ->andWhere('deletion_date IS NULL')
            ->setParameter('userId', $userId)
            ->setParameter('contextId', $contextId)
            ->setParameter('types', self::REDACTED_TYPES, ArrayParameterType::STRING)
            ->executeQuery()
            ->fetchAllAssociative();

        $labelIds = [];
        foreach ($rows as $row) {
            $extras = unserialize((string) $row['extras'], ['allowed_classes' => false]);
            if (1 !== (int) (is_array($extras) ? ($extras['SYSTEM_LABEL'] ?? 0) : 0)) {
                $labelIds[] = (int) $row['item_id'];
            }
        }
        if ([] === $labelIds) {
            return;
        }

        $this->connection->createQueryBuilder()
            ->update('labels')
            ->set('description', ':description')
            ->set('modification_date', 'NOW()')
            ->where('item_id IN (:labelIds)')
            ->setParameter('description', $this->redactionText->description($contextId))
            ->setParameter('labelIds', $labelIds, ArrayParameterType::INTEGER)
            ->executeStatement();

        // The attachments go with the description they belong to.
        foreach ($labelIds as $labelId) {
            $this->fileDeleter->detachFromItem($labelId, $userId);
        }
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        foreach (['creator_id', 'modifier_id'] as $column) {
            $this->connection->createQueryBuilder()
                ->update('labels')
                ->set($column, 'NULL')
                ->where("$column = :userId")
                ->andWhere('context_id = :contextId')
                ->setParameter('userId', $userId)
                ->setParameter('contextId', $contextId)
                ->executeStatement();
        }
    }
}
