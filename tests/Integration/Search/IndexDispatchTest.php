<?php

declare(strict_types=1);

namespace Tests\Integration\Search;

use App\Event\ItemDeletedEvent;
use App\Event\ItemReindexEvent;
use App\EventSubscriber\ElasticaSubscriber;
use App\Legacy\LegacyIndexDispatcher;
use App\Message\ReindexItem;
use App\Message\RemoveItemFromIndex;
use cs_item;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * Indexing must leave the request that saved an item: a document the search server
 * refuses may not reach the caller, and a search server that is briefly away must be
 * retried rather than silently skipped.
 *
 * Both seams are covered — the modern event and the legacy `cs_item` path — because
 * they used to write to Elasticsearch independently of each other.
 */
class IndexDispatchTest extends KernelTestCase
{
    private InMemoryTransport $transport;

    public function testTheEventPathQueuesInsteadOfIndexing(): void
    {
        // the subscriber is exercised directly: the shared events carry unrelated
        // listeners whose needs have nothing to do with indexing
        self::getContainer()->get(ElasticaSubscriber::class)
            ->onItemReindex(new ItemReindexEvent($this->item(4711, 'material')));

        $messages = $this->transport->getSent();
        self::assertCount(1, $messages);

        $message = $messages[0]->getMessage();
        self::assertInstanceOf(ReindexItem::class, $message);
        self::assertSame(4711, $message->getItemId());
        self::assertSame('material', $message->getItemType());
    }

    public function testTheLegacyPathQueuesInsteadOfIndexing(): void
    {
        self::getContainer()->get(LegacyIndexDispatcher::class)->reindex(4711, 'material');

        $messages = $this->transport->getSent();
        self::assertCount(1, $messages);
        self::assertInstanceOf(ReindexItem::class, $messages[0]->getMessage());
    }

    public function testDeletionQueuesARemoval(): void
    {
        self::getContainer()->get(ElasticaSubscriber::class)
            ->onItemDeleted(new ItemDeletedEvent($this->item(4711, 'material')));

        $messages = $this->transport->getSent();
        self::assertCount(1, $messages);
        self::assertInstanceOf(RemoveItemFromIndex::class, $messages[0]->getMessage());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function roomTypes(): iterable
    {
        yield 'project room' => ['project'];
        yield 'community room' => ['community'];
        yield 'group room' => ['grouproom'];
        yield 'user room' => ['userroom'];
    }

    /**
     * A room is written to the index when it is saved, not only removed from it.
     */
    #[DataProvider('roomTypes')]
    public function testRoomsAreWrittenAndRemoved(string $roomType): void
    {
        $dispatcher = self::getContainer()->get(LegacyIndexDispatcher::class);

        $dispatcher->reindex(4711, $roomType);
        $dispatcher->remove(4711, $roomType);

        $messages = array_map(static fn ($envelope): object => $envelope->getMessage(), $this->transport->getSent());
        self::assertCount(2, $messages);
        self::assertInstanceOf(ReindexItem::class, $messages[0]);
        self::assertInstanceOf(RemoveItemFromIndex::class, $messages[1]);
    }

    /**
     * Private rooms are neither written to the index nor removed from it.
     */
    public function testPrivateRoomsAreLeftAlone(): void
    {
        $dispatcher = self::getContainer()->get(LegacyIndexDispatcher::class);

        $dispatcher->reindex(4711, 'privateroom');
        $dispatcher->remove(4711, 'privateroom');

        self::assertCount(0, $this->transport->getSent());
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->transport = self::getContainer()->get('messenger.transport.async');
        $this->transport->reset();
    }

    private function item(int $itemId, string $itemType): cs_item
    {
        $item = $this->createMock(cs_item::class);
        $item->method('getItemID')->willReturn($itemId);
        $item->method('getItemType')->willReturn($itemType);

        return $item;
    }
}
