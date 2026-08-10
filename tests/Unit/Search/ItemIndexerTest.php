<?php

declare(strict_types=1);

namespace Tests\Unit\Search;

use App\Entity\Items;
use App\Entity\Materials;
use App\Item\TypedEntityResolver;
use App\Search\ItemIndexer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOS\ElasticaBundle\Index\IndexManager;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use FOS\ElasticaBundle\Persister\PersisterRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Elastica\Bulk\Response as BulkResponse;
use Elastica\Bulk\ResponseSet;
use Elastica\Exception\Bulk\ResponseException as BulkResponseException;
use Elastica\Exception\ConnectionException;
use Elastica\Exception\ResponseException;
use Elastica\Request;
use Elastica\Response;
use Psr\Log\LoggerInterface;

class ItemIndexerTest extends TestCase
{
    private const MATERIAL = 'material';

    private MockObject $persister;
    private MockObject $logger;

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
        $this->persister->method('insertOne')->willThrowException(new ConnectionException('no route'));
        $this->logger->expects(self::never())->method('error');

        $this->expectException(ConnectionException::class);

        $this->indexer()->reindex(42, self::MATERIAL);
    }

    public function testLetsAServerSideErrorThrough(): void
    {
        $this->persister->method('insertOne')->willThrowException($this->rejection(503));

        $this->expectException(ResponseException::class);

        $this->indexer()->reindex(42, self::MATERIAL);
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

    private function rejection(int $status): ResponseException
    {
        $response = $this->createMock(Response::class);
        $response->method('getStatus')->willReturn($status);

        return new ResponseException($this->createMock(Request::class), $response);
    }

    protected function setUp(): void
    {
        $this->persister = $this->createMock(ObjectPersisterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
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

        return new ItemIndexer(
            $entityManager,
            $resolver,
            $registry,
            $this->createMock(IndexManager::class),
            $this->logger,
            $elasticsearchUrl
        );
    }
}
