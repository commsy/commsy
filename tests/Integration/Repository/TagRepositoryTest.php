<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Entity\Room;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins the category tree the export ships against what the room actually holds.
 */
final class TagRepositoryTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Connection $connection;
    private TagRepository $tags;
    private Room $room;
    private User $roomUser;

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithoutCategoriesHasAnEmptyTree(): void
    {
        $this->assertSame([], $this->tags->findCategoryTreeFor($this->roomId()));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testTopLevelCategoriesComeBackWithoutTheTechnicalRoot(): void
    {
        $this->createCategory('Vorlesung');
        $this->createCategory('Übung');

        $tree = $this->tags->findCategoryTreeFor($this->roomId());

        $this->assertSame(['Übung', 'Vorlesung'], array_column($tree, 'title'));
        $this->assertSame([[], []], array_column($tree, 'children'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testChildrenHangUnderTheirParent(): void
    {
        $parent = $this->createCategory('Vorlesung');
        $this->createCategory('Folien', $parent);
        $this->createCategory('Aufzeichnung', $parent);

        $tree = $this->tags->findCategoryTreeFor($this->roomId());

        $this->assertCount(1, $tree);
        $this->assertSame('Vorlesung', $tree[0]['title']);
        $this->assertSame(['Aufzeichnung', 'Folien'], array_column($tree[0]['children'], 'title'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testTheTreeGoesDeeperThanOneLevel(): void
    {
        $parent = $this->createCategory('Vorlesung');
        $child = $this->createCategory('Folien', $parent);
        $this->createCategory('Woche 1', $child);

        $tree = $this->tags->findCategoryTreeFor($this->roomId());

        $this->assertSame('Woche 1', $tree[0]['children'][0]['children'][0]['title']);
    }

    /**
     * The export is compared between runs, so a level's order is fixed here —
     * legacy leaves it undefined.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testALevelIsOrderedByTitle(): void
    {
        foreach (['Zebra', 'alpha', 'Mitte'] as $title) {
            $this->createCategory($title);
        }

        $tree = $this->tags->findCategoryTreeFor($this->roomId());

        $this->assertSame(['alpha', 'Mitte', 'Zebra'], array_column($tree, 'title'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedCategoryIsLeftOut(): void
    {
        $this->createCategory('Bleibt');
        $gone = $this->createCategory('Weg');

        $this->connection->executeStatement(
            'UPDATE tag SET deletion_date = NOW(), deleter_id = :deleter WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $gone]
        );

        $tree = $this->tags->findCategoryTreeFor($this->roomId());

        $this->assertSame(['Bleibt'], array_column($tree, 'title'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testTheCategoriesOfAnotherRoomStayOut(): void
    {
        $this->createCategory('Eigene');

        $this->assertSame([], $this->tags->findCategoryTreeFor($this->roomId() + 9999));
    }

    /**
     * tag2tag is a net by construction; one loop would recurse for ever.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testALoopInTheTreeDoesNotRecurseForEver(): void
    {
        $parent = $this->createCategory('Vorlesung');
        $child = $this->createCategory('Folien', $parent);

        // Close the circle behind the export's back.
        $this->connection->executeStatement(
            'INSERT INTO tag2tag (from_item_id, to_item_id, context_id, creation_date, creator_id)
             VALUES (:from, :to, :context, NOW(), :creator)',
            [
                'from' => $child,
                'to' => $parent,
                'context' => $this->roomId(),
                'creator' => $this->roomUser->getItemId(),
            ]
        );

        $tree = $this->tags->findCategoryTreeFor($this->roomId());

        $this->assertSame('Vorlesung', $tree[0]['title']);
        $this->assertSame('Folien', $tree[0]['children'][0]['title']);
        $this->assertSame([], $tree[0]['children'][0]['children'], 'the loop stops here');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testItFindsTheSameCategoriesAsTheLegacyReader(): void
    {
        $parent = $this->createCategory('Vorlesung');
        $this->createCategory('Folien', $parent);
        $this->createCategory('Übung');

        $mine = $this->flatten($this->tags->findCategoryTreeFor($this->roomId()));

        $legacy = [];
        foreach (self::getContainer()->get(CategoryService::class)->getTags($this->roomId()) as $row) {
            $legacy[] = (int) $row['item_id'];
            foreach ($row['children'] as $child) {
                $legacy[] = (int) $child['item_id'];
            }
        }

        sort($mine);
        sort($legacy);

        $this->assertSame($legacy, $mine);
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->tags = self::getContainer()->get(TagRepository::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        $this->primeLegacyEnvironment($this->room, $this->roomUser);
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return self::getContainer()->get(LegacyEnvironment::class);
    }

    private function roomId(): int
    {
        return $this->room->getItemId();
    }

    private function createCategory(string $title, ?int $parentId = null): int
    {
        return (int) self::getContainer()->get(CategoryService::class)
            ->addTag($title, $this->roomId(), $parentId)
            ->getItemID();
    }

    /**
     * @param list<array<string, mixed>> $tree
     *
     * @return list<int>
     */
    private function flatten(array $tree): array
    {
        $ids = [];
        foreach ($tree as $node) {
            $ids[] = $node['id'];
            $ids = [...$ids, ...$this->flatten($node['children'])];
        }

        return $ids;
    }
}
