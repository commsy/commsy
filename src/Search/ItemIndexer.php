<?php

declare(strict_types=1);

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

namespace App\Search;

use App\Entity\Items;
use App\Item\ItemType;
use App\Item\TypedEntityResolver;
use App\Room\RoomType;
use App\Rubric\RubricType;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Exception\Bulk\ResponseException as BulkResponseException;
use Elastica\Exception\ResponseException;
use FOS\ElasticaBundle\Index\IndexManager;
use FOS\ElasticaBundle\Persister\PersisterRegistry;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Writes a single item to its Elasticsearch index, or removes it.
 *
 * The one place that talks to the persisters, so the modern event path and the legacy
 * `cs_item::updateElastic()` path cannot drift apart. Everything is addressed by item
 * id and type rather than by object: the caller may be a legacy item, a Doctrine
 * entity or a queued message, and the current state is read here in every case.
 *
 * Indexing failures are contained. A document Elasticsearch refuses — an attachment
 * Tika cannot parse, for instance — must not take down the request that saved the
 * item; the entry stays out of the search index and the reason is logged.
 */
readonly class ItemIndexer
{
    /**
     * Item type → index name for writing. Tasks and labels are intentionally absent:
     * they were never reindexed via the legacy `updateElastic()` path either.
     *
     * @var array<string, string>
     */
    private const REINDEX_INDEX_BY_TYPE = [
        RubricType::Announcement->value => 'commsy_announcement',
        RubricType::Date->value => 'commsy_date',
        RubricType::Discussion->value => 'commsy_discussion',
        RubricType::Material->value => 'commsy_material',
        RubricType::Todo->value => 'commsy_todo',
        ItemType::User->value => 'commsy_user',
    ];

    /**
     * Item type → index name for removal. Deliberately wider than the write table:
     * room types share a single `commsy_room` index and are cleaned up on delete even
     * though rooms are not written through this service. Private rooms and user rooms
     * stay absent — legacy never removed them either, and that parity is preserved.
     *
     * @var array<string, string>
     */
    private const DELETE_INDEX_BY_TYPE = [
        RubricType::Announcement->value => 'commsy_announcement',
        RoomType::Community->value => 'commsy_room',
        RubricType::Date->value => 'commsy_date',
        RubricType::Discussion->value => 'commsy_discussion',
        RoomType::GroupRoom->value => 'commsy_room',
        RubricType::Label->value => 'commsy_label',
        RubricType::Material->value => 'commsy_material',
        RoomType::Project->value => 'commsy_room',
        RubricType::Todo->value => 'commsy_todo',
        ItemType::User->value => 'commsy_user',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TypedEntityResolver $typedEntityResolver,
        #[Autowire(service: 'fos_elastica.persister_registry')]
        private PersisterRegistry $persisterRegistry,
        private IndexManager $indexManager,
        private LoggerInterface $logger,
        #[Autowire('%env(ELASTICSEARCH_URL)%')]
        private string $elasticsearchUrl = '',
    ) {
    }

    /**
     * Whether queuing is worthwhile at all. Callers ask before dispatching so an
     * installation without a search index does not fill the queue with work the
     * handler would only drop again.
     */
    public function isReindexable(string $itemType): bool
    {
        return $this->isConfigured() && isset(self::REINDEX_INDEX_BY_TYPE[$itemType]);
    }

    public function isRemovable(string $itemType): bool
    {
        return $this->isConfigured() && isset(self::DELETE_INDEX_BY_TYPE[$itemType]);
    }

    /**
     * Writes the item's current state to its index.
     *
     * A plain insert is enough and deliberately not preceded by a delete: the
     * persisters carry `pipeline => attachment` (see ElasticaCompilerPass), so
     * indexing an existing id replaces the document and still runs the ingest
     * pipeline. Deleting first only meant that a rejected document left the item
     * missing from the index altogether instead of keeping the previous version.
     */
    public function reindex(int $itemId, string $itemType): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $indexName = self::REINDEX_INDEX_BY_TYPE[$itemType] ?? null;
        if (null === $indexName) {
            return;
        }

        // drafts are not searchable, and the flag lives on the shared items row
        $item = $this->entityManager->getRepository(Items::class)->find($itemId);
        if (null === $item || $item->isDraft()) {
            return;
        }

        $object = $this->typedEntityResolver->find($itemId);
        if (null === $object || !$object->isIndexable()) {
            return;
        }

        try {
            $persister = $this->persisterRegistry->getPersister($indexName);
        } catch (InvalidArgumentException) {
            return;
        }

        try {
            $persister->insertOne($object);
        } catch (BulkResponseException|ResponseException $e) {
            // Retrying a document the server rejected would fail exactly the same way,
            // so this one is logged and considered done. Everything else — a connection
            // that dropped, a cluster that is briefly unwell — is left to propagate so
            // the worker retries it and the failure transport keeps what never succeeds.
            if (!$this->isPermanentRejection($e)) {
                throw $e;
            }

            $this->logger->error('Search server rejected the document; the entry stays out of the index.', [
                'itemId' => $itemId,
                'itemType' => $itemType,
                'index' => $indexName,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Whether the server refused the document itself rather than failing to take it.
     *
     * A 4xx means the request will never be accepted in this form — a malformed
     * document, or an attachment the ingest pipeline cannot parse. Anything else is
     * treated as worth another attempt.
     */
    private function isPermanentRejection(BulkResponseException|ResponseException $exception): bool
    {
        $statuses = [];

        if ($exception instanceof ResponseException) {
            $statuses[] = $exception->getResponse()->getStatus();
        } else {
            // Bulk\Response does not pass the per-action status to its parent, so
            // getStatus() is empty there: the status of the individual action lives in
            // the response data. The bulk request itself answers 200 even when every
            // action in it failed.
            foreach ($exception->getResponseSet()->getBulkResponses() as $bulkResponse) {
                if (!$bulkResponse->isOk()) {
                    $statuses[] = (int) ($bulkResponse->getData()['status'] ?? 0);
                }
            }
        }

        $statuses = array_filter($statuses);
        if ([] === $statuses) {
            return false;
        }

        foreach ($statuses as $status) {
            if ($status < 400 || $status >= 500) {
                return false;
            }
        }

        return true;
    }

    public function remove(int $itemId, string $itemType): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $indexName = self::DELETE_INDEX_BY_TYPE[$itemType] ?? null;
        if (null === $indexName) {
            return;
        }

        try {
            $this->indexManager->getIndex($indexName)->deleteById((string) $itemId);
        } catch (ResponseException $e) {
            // 404 responses (missing document/index) are not fatal; swallow them.
            if (404 !== $e->getResponse()->getStatus()) {
                $this->logger->error('Removing item from the search index failed.', [
                    'itemId' => $itemId,
                    'itemType' => $itemType,
                    'index' => $indexName,
                    'exception' => $e,
                ]);
            }
        } catch (InvalidArgumentException) {
            // IndexManager throws this when the index name is unknown — skip silently.
        }
    }

    private function isConfigured(): bool
    {
        return '' !== $this->elasticsearchUrl;
    }
}
