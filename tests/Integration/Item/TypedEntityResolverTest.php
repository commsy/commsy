<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Item;

use App\Entity\Announcement;
use App\Entity\Materials;
use App\Entity\Section;
use App\Entity\Todos;
use App\Item\TypedEntityResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\SectionFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Pins that resolving an item id yields a fully loaded rubric entity.
 *
 * Doctrine used to do this through a DiscriminatorMap on `Items`. Because no
 * rubric class extends `Items`, it instantiated the mapped class but filled
 * only the `items` columns — so everything the rubric table owns stayed
 * uninitialised. On a typed property that is a fatal on first read, which is
 * how Materials::$versionId broke saving a section (#5009).
 *
 * The version assertions below are the actual regression guard: reading them
 * is what used to throw.
 */
#[WithStory(RoomWithMemberStory::class)]
class TypedEntityResolverTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private TypedEntityResolver $resolver;
    private array $owner;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->resolver = self::getContainer()->get(TypedEntityResolver::class);
        $this->owner = [
            'room' => RoomWithMemberStory::get('room'),
            'creator' => RoomWithMemberStory::get('roomUser'),
        ];
    }

    public function testResolvesMaterialWithItsVersionReadable(): void
    {
        $material = MaterialFactory::createOne($this->owner);

        $resolved = $this->resolver->find($material->getItemId());

        self::assertInstanceOf(Materials::class, $resolved);
        self::assertSame($material->getItemId(), $resolved->getItemId());
        // The read that used to throw UninitializedPropertyException.
        self::assertIsInt($resolved->getVersionId());
    }

    public function testResolvesSectionWithItsVersionReadable(): void
    {
        $section = SectionFactory::createOne(
            $this->owner + ['material' => MaterialFactory::createOne($this->owner)]
        );

        $resolved = $this->resolver->find($section->getItemId());

        self::assertInstanceOf(Section::class, $resolved);
        self::assertIsInt($resolved->getVersionId());
    }

    public function testResolvesUnversionedRubrics(): void
    {
        $announcement = AnnouncementFactory::createOne($this->owner);
        $todo = TodoFactory::createOne($this->owner);

        self::assertInstanceOf(Announcement::class, $this->resolver->find($announcement->getItemId()));
        self::assertInstanceOf(Todos::class, $this->resolver->find($todo->getItemId()));
    }

    public function testReturnsNullForUnknownId(): void
    {
        self::assertNull($this->resolver->find(999999));
    }
}
