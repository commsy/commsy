<?php

declare(strict_types=1);

namespace Tests\Unit\Search;

use App\Entity\Items;
use App\Entity\Materials;
use App\Item\TypedEntityResolver;
use App\Search\ItemIndexer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use FOS\ElasticaBundle\Elastica\Index;
use FOS\ElasticaBundle\Index\IndexManager;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use FOS\ElasticaBundle\Persister\PersisterRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Elastica\Bulk\Response as BulkResponse;
use Elastica\Bulk\ResponseSet;
use Elastica\Exception\Bulk\ResponseException as BulkResponseException;
use Elastica\Exception\ClientException;
use Psr\Log\LoggerInterface;

class ItemIndexerTest extends TestCase
{
    private const MATERIAL = 'material';

    private MockObject $persister;
    private MockObject $logger;
    private MockObject $index;

    public function testWritesTheItemWithoutDeletingItFirst(): void
    {
        // A delete before the insert is what used to make a rejected document remove
        // the item from the index entirely instead of leaving the previous version.
        $this->persister->expects(self::never())->method('deleteOne');
        $this->persister->expects(self::once())->method('insertOne');

        $this->indexer()->reindex(42, self::MATERIAL);
    }

    public function testSkipsWhenNoSearchServerIsConfigured(): void
    {
        $this->persister->expects(self::never())->method('insertOne');

        $this->indexer(elasticsearchUrl: '')->reindex(42, self::MATERIAL);
    }

    public function testSkipsItemTypesThatAreNotIndexed(): void
    {
        $this->persister->expects(self::never())->method('insertOne');

        $this->indexer()->reindex(42, 'task');
    }

    public function testSkipsDrafts(): void
    {
        $this->persister->expects(self::never())->method('insertOne');

        $this->indexer(draft: true)->reindex(42, self::MATERIAL);
    }

    public function testSkipsItemsThatAreNotIndexable(): void
    {
        $this->persister->expects(self::never())->method('insertOne');

        $this->indexer(indexable: false)->reindex(42, self::MATERIAL);
    }

    /**
     * The point of the whole exercise: a document the search server refuses — an
     * attachment Tika cannot parse, for instance — must not reach the caller.
     */
    public function testContainsAndLogsARejectedDocument(): void
    {
        $this->persister->method('insertOne')->willThrowException($this->rejection(400));
        $this->logger->expects(self::once())->method('error');

        $this->indexer()->reindex(42, self::MATERIAL);
    }

    /**
     * A server that failed to take the document, rather than refusing it, has to stay
     * visible: the worker retries it and the failure transport keeps what never lands.
     * Swallowing this was what made the queue pointless.
     */
    public function testLetsATransientFailureThrough(): void
    {
        $this->persister->method('insertOne')->willThrowException(new NoNodeAvailableException('no route'));
        $this->logger->expects(self::never())->method('error');

        $this->expectException(NoNodeAvailableException::class);

        $this->indexer()->reindex(42, self::MATERIAL);
    }

    public function testLetsAServerSideErrorThrough(): void
    {
        $this->persister->method('insertOne')->willThrowException($this->rejection(503));

        $this->expectException(ServerResponseException::class);

        $this->indexer()->reindex(42, self::MATERIAL);
    }

    /**
     * The bundle wraps the codes in its http_error_codes (400, 403) into its own
     * exception; the status is on the one it wraps.
     */
    public function testRecognisesARejectionTheBundleWrapped(): void
    {
        $this->persister->method('insertOne')->willThrowException(
            new ClientException('refused', 0, $this->rejection(400))
        );
        $this->logger->expects(self::once())->method('error');

        $this->indexer()->reindex(42, self::MATERIAL);
    }

    /**
     * Elastica 9 answers a delete of a document that was never indexed with a 404
     * exception; that is the common case for drafts and must stay silent.
     */
    public function testRemovingADocumentThatIsNotThereIsNoError(): void
    {
        $this->index->method('deleteById')->willThrowException($this->rejection(404));
        $this->logger->expects(self::never())->method('error');

        $this->indexer()->remove(42, self::MATERIAL);
    }

    public function testLogsAFailedRemoval(): void
    {
        $this->index->method('deleteById')->willThrowException($this->rejection(503));
        $this->logger->expects(self::once())->method('error');

        $this->indexer()->remove(42, self::MATERIAL);
    }

    /**
     * The shape that actually occurs in production: FOS Elastica writes through the
     * bulk API, and a bulk request answers 200 even when the action inside it was
     * refused. Reading the outer status here would retry a document forever.
     */
    public function testRecognisesARejectionInsideABulkResponse(): void
    {
        $this->persister->method('insertOne')->willThrowException($this->bulkRejection(400));
        $this->logger->expects(self::once())->method('error');

        $this->indexer()->reindex(42, self::MATERIAL);
    }

    public function testLetsAServerSideErrorInsideABulkResponseThrough(): void
    {
        $this->persister->method('insertOne')->willThrowException($this->bulkRejection(503));

        $this->expectException(BulkResponseException::class);

        $this->indexer()->reindex(42, self::MATERIAL);
    }

    private function bulkRejection(int $status): BulkResponseException
    {
        $bulkResponse = $this->createMock(BulkResponse::class);
        $bulkResponse->method('isOk')->willReturn(false);
        $bulkResponse->method('getData')->willReturn(['status' => $status]);

        $responseSet = $this->createMock(ResponseSet::class);
        $responseSet->method('getBulkResponses')->willReturn([$bulkResponse]);

        $exception = $this->createMock(BulkResponseException::class);
        $exception->method('getResponseSet')->willReturn($responseSet);

        return $exception;
    }

    private function rejection(int $status): ClientResponseException|ServerResponseException
    {
        // The client raises these with the HTTP status as code.
        return $status >= 500
            ? new ServerResponseException("$status", $status)
            : new ClientResponseException("$status", $status);
    }

    protected function setUp(): void
    {
        $this->persister = $this->createMock(ObjectPersisterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->index = $this->createMock(Index::class);
    }

    private function indexer(
        bool $draft = false,
        bool $indexable = true,
        string $elasticsearchUrl = 'http://elastic.invalid:9200/',
    ): ItemIndexer {
        $item = $this->createMock(Items::class);
        $item->method('isDraft')->willReturn($draft);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('find')->willReturn($item);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $material = $this->createMock(Materials::class);
        $material->method('isIndexable')->willReturn($indexable);

        $resolver = $this->createMock(TypedEntityResolver::class);
        $resolver->method('find')->willReturn($material);

        $registry = $this->createMock(PersisterRegistry::class);
        $registry->method('getPersister')->willReturn($this->persister);

        $indexManager = $this->createMock(IndexManager::class);
        $indexManager->method('getIndex')->willReturn($this->index);

        return new ItemIndexer(
            $entityManager,
            $resolver,
            $registry,
            $indexManager,
            $this->logger,
            $elasticsearchUrl
        );
    }
}
