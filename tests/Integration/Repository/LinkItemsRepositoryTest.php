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
use App\Repository\LinkItemsRepository;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\LabelService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\LinkItemFactory;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The `link_items` table carries an entry's categories. These tests pin the
 * reading against what legacy does, so the export and the interface cannot
 * disagree with the pages the categories are assigned on.
 */
final class LinkItemsRepositoryTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private const CATEGORY_TYPE = 'tag';

    private Connection $connection;
    private LinkItemsRepository $linkItems;
    private Room $room;
    private User $roomUser;

    #[WithStory(RoomWithMemberStory::class)]
    public function testItFindsACategoryLinkedToAnEntry(): void
    {
        $material = $this->createMaterial();
        $category = $this->createCategory('Kategorie');

        $this->link($material, $category);

        $this->assertSame(
            [$category],
            $this->linkItems->findLinkedItemIds($material, self::CATEGORY_TYPE, $this->roomId())
        );
    }

    /**
     * A pair is stored in whichever order it was created, so the lookup has to
     * find the entry on either side and constrain the type of the other.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheEntryIsFoundOnEitherSideOfTheLink(): void
    {
        $material = $this->createMaterial();
        $category = $this->createCategory('Kategorie');

        $this->link($category, $material);

        $this->assertSame(
            [$category],
            $this->linkItems->findLinkedItemIds($material, self::CATEGORY_TYPE, $this->roomId())
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testItAgreesWithTheLegacyReader(): void
    {
        $material = $this->createMaterial();
        $first = $this->createCategory('Erste');
        $second = $this->createCategory('Zweite');

        $this->link($material, $first);
        $this->link($second, $material);

        $mine = $this->linkItems->findLinkedItemIds($material, self::CATEGORY_TYPE, $this->roomId());

        $legacyItem = self::getContainer()->get(LegacyEnvironment::class)
            ->getEnvironment()->getItemManager()->getItem($material);
        $legacy = array_map(
            'intval',
            self::getContainer()->get(LabelService::class)->getLinkedCategoryIds($legacyItem)
        );

        sort($mine);
        sort($legacy);

        $this->assertSame($legacy, $mine);
        $this->assertSame([$first, $second], $mine);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAnotherItemTypeIsNotReturned(): void
    {
        $material = $this->createMaterial();
        $otherMaterial = $this->createMaterial();

        $this->link($material, $otherMaterial);

        // The link exists, but the other end is a material and not a category.
        $this->assertSame(
            [],
            $this->linkItems->findLinkedItemIds($material, self::CATEGORY_TYPE, $this->roomId())
        );
        $this->assertSame(
            [$otherMaterial],
            $this->linkItems->findLinkedItemIds($material, 'material', $this->roomId())
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedLinkIsNotReturned(): void
    {
        $material = $this->createMaterial();
        $category = $this->createCategory('Kategorie');

        $linkItemId = $this->link($material, $category);

        $this->connection->executeStatement(
            'UPDATE link_items SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $linkItemId]
        );

        $this->assertSame(
            [],
            $this->linkItems->findLinkedItemIds($material, self::CATEGORY_TYPE, $this->roomId())
        );
    }

    /**
     * Legacy leaves the room open for most rubric entries. Passing one keeps an
     * export to the content of its own room.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheRoomLimitIsAppliedWhenGivenAndSkippedWhenNot(): void
    {
        $material = $this->createMaterial();
        $category = $this->createCategory('Kategorie');

        $this->link($material, $category);

        $this->assertSame(
            [],
            $this->linkItems->findLinkedItemIds($material, self::CATEGORY_TYPE, $this->roomId() + 9999),
            'a foreign room finds nothing'
        );
        $this->assertSame(
            [$category],
            $this->linkItems->findLinkedItemIds($material, self::CATEGORY_TYPE),
            'without a room the link is found regardless, as in legacy'
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAnEntryWithoutLinksComesBackEmpty(): void
    {
        $material = $this->createMaterial();

        $this->assertSame(
            [],
            $this->linkItems->findLinkedItemIds($material, self::CATEGORY_TYPE, $this->roomId())
        );
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->linkItems = self::getContainer()->get(LinkItemsRepository::class);

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

    private function createMaterial(): int
    {
        return MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ])->getItemId();
    }

    private function createCategory(string $title): int
    {
        return (int) self::getContainer()->get(CategoryService::class)
            ->addTag($title, $this->roomId())
            ->getItemID();
    }

    private function link(int $firstItemId, int $secondItemId): int
    {
        return LinkItemFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'firstItemId' => $firstItemId,
            'secondItemId' => $secondItemId,
        ])->getItemId();
    }
}
