<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Item;

use App\Entity\Announcement;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\Section;
use App\Entity\Todos;
use App\Item\ItemType;
use App\Item\ItemTypeMap;
use App\Room\RoomType;
use App\Rubric\RubricType;
use PHPUnit\Framework\TestCase;

final class ItemTypeMapTest extends TestCase
{
    private ItemTypeMap $map;

    protected function setUp(): void
    {
        $this->map = new ItemTypeMap();
    }

    public function testNamesTheEntityClassForAType(): void
    {
        self::assertSame(Materials::class, $this->map->classFor('material'));
        self::assertSame(Section::class, $this->map->classFor('section'));
        self::assertSame(Room::class, $this->map->classFor('project'));
    }

    public function testGivesNoClassForAnUnmappedType(): void
    {
        self::assertNull($this->map->classFor('file'));
        self::assertNull($this->map->classFor(''));
    }

    /**
     * The reverse direction, which ItemEditDispatcher uses to pick the
     * per-rubric permission override.
     */
    public function testNamesTheTypeOfAnEntity(): void
    {
        self::assertSame('material', $this->map->typeOf(new Materials()));
        self::assertSame('section', $this->map->typeOf(new Section()));
        self::assertSame('announcement', $this->map->typeOf(new Announcement()));
        self::assertSame('todo', $this->map->typeOf(new Todos()));
    }

    /**
     * All four room flavours are the same entity, so an instance cannot say
     * which type it came from. Reporting none beats reporting a guess.
     */
    public function testGivesNoTypeForAmbiguousOrUnmappedClasses(): void
    {
        self::assertNull($this->map->typeOf(new Room()));
        self::assertNull($this->map->typeOf(new \stdClass()));
    }

    /**
     * A Doctrine proxy is a subclass of the entity, so it must answer the
     * same as the entity it stands in for.
     */
    public function testAnswersForSubclassesSuchAsDoctrineProxies(): void
    {
        $proxy = new class extends Materials {};

        self::assertSame('material', $this->map->typeOf($proxy));
    }

    /**
     * Every mapped type must be a case of one of the enums that own
     * `items.type`, so the strings stay declared in exactly one place.
     */
    public function testEveryMappedTypeIsBackedByAnEnumCase(): void
    {
        foreach ($this->map->knownTypes() as $type) {
            self::assertNotNull(
                RubricType::tryFrom($type) ?? ItemType::tryFrom($type) ?? RoomType::tryFrom($type),
                sprintf('Item type "%s" has no case in RubricType, ItemType or RoomType.', $type)
            );
        }
    }

    /**
     * Every room flavour is a `room` row, so every one of them has to resolve
     * to `Room`. The DiscriminatorMap this table replaced forgot `userroom`,
     * which left user rooms resolving to nothing at all.
     */
    public function testResolvesEveryRoomFlavourToTheRoomEntity(): void
    {
        foreach (RoomType::cases() as $roomType) {
            self::assertSame(
                Room::class,
                $this->map->classFor($roomType->value),
                sprintf('Room type "%s" does not resolve to the Room entity.', $roomType->value)
            );
        }
    }

    /**
     * Pins the mapped set so a type cannot quietly go missing again.
     */
    public function testCoversTheKnownItemTypes(): void
    {
        self::assertSame([
            'annotation', 'announcement', 'assessments', 'community', 'date',
            'discarticle', 'discussion', 'grouproom', 'label', 'link_item',
            'material', 'privateroom', 'project', 'section', 'server', 'step',
            'tag', 'task', 'todo', 'user', 'userroom',
        ], $this->map->knownTypes());
    }
}
