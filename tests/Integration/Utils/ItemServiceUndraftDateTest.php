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

namespace Tests\Integration\Utils;

use App\Entity\Dates;
use App\Entity\Room;
use App\Entity\User;
use App\Message\ReindexItem;
use App\Utils\ItemService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Tests\Factory\DatesFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A draft is not indexed; the entry reaches the search index when the draft
 * flag is lifted. Saving a new date queues its reindex while it is still a
 * draft, and the worker may run it before the flag is lifted — undraft() has
 * to queue it again.
 */
final class ItemServiceUndraftDateTest extends KernelTestCase
{
    private Connection $connection;
    private InMemoryTransport $transport;
    private Room $room;
    private User $roomUser;

    #[WithStory(RoomWithMemberStory::class)]
    public function testUndraftingADateQueuesItsReindex(): void
    {
        $date = $this->createDraftDate();

        self::getContainer()->get(ItemService::class)->undraft($date->getItemId());

        self::assertSame(0, $this->draftFlag($date), 'the draft flag must be lifted');
        self::assertSame([$date->getItemId()], $this->queuedDateIds());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testUndraftingASeriesQueuesEveryDateOfIt(): void
    {
        $first = $this->createDraftDate();
        $second = $this->createDraftDate($first->getItemId());
        $bystander = $this->createDraftDate();

        self::getContainer()->get(ItemService::class)->undraft($first->getItemId());

        $expected = [$first->getItemId(), $second->getItemId()];
        $queued = $this->queuedDateIds();
        sort($expected);
        sort($queued);
        self::assertSame($expected, $queued);
        self::assertSame(1, $this->draftFlag($bystander), 'an unrelated draft stays a draft');
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $this->transport = self::getContainer()->get('messenger.transport.async');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
    }

    private function createDraftDate(?int $recurrenceId = null): Dates
    {
        $date = DatesFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'recurrenceId' => $recurrenceId,
        ]);

        $this->connection->executeStatement(
            'UPDATE items SET draft = 1 WHERE item_id = :id',
            ['id' => $date->getItemId()]
        );
        $this->transport->reset();

        return $date;
    }

    private function draftFlag(Dates $date): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT draft FROM items WHERE item_id = :id',
            ['id' => $date->getItemId()]
        );
    }

    /**
     * @return int[]
     */
    private function queuedDateIds(): array
    {
        $ids = [];
        foreach ($this->transport->getSent() as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof ReindexItem && 'date' === $message->getItemType()) {
                $ids[] = $message->getItemId();
            }
        }

        return array_values(array_unique($ids));
    }
}
