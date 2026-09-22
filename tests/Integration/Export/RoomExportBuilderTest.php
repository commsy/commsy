<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Export;

use App\Entity\Room;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use App\Rubric\RubricType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * What lands in the document is decided by the `export` group on the entity, not
 * by this builder. These tests pin that the group is what governs — a field
 * without it stays out even though the column is right there.
 */
class RoomExportBuilderTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testTheDocumentCarriesTheRoomsExportFields(): void
    {
        self::bootKernel();

        $room = $this->managedRoom(RoomFactory::createOne([
            'title' => 'Projektraum',
            'type' => 'project',
            'roomDescription' => 'Eine Beschreibung',
            'archived' => false,
            'portal' => PortalFactory::createOne(),
        ])->getItemId());

        $document = self::getContainer()->get(RoomExportBuilder::class)->build($room)->content;

        $this->assertSame(RoomExportBuilder::FORMAT_VERSION, $document['formatVersion']);

        $this->assertSame('Projektraum', $document['room']['title']);
        $this->assertSame('project', $document['room']['type']);
        $this->assertSame('Eine Beschreibung', $document['room']['roomDescription']);
        $this->assertFalse($document['room']['archived']);
        $this->assertSame($room->getItemId(), $document['room']['itemId']);

        // From EntityDatesTrait, shared by every rubric.
        $this->assertArrayHasKey('creationDate', $document['room']);
        $this->assertArrayHasKey('modificationDate', $document['room']);
    }

    public function testFieldsWithoutTheExportGroupStayOut(): void
    {
        self::bootKernel();

        $room = $this->managedRoom(RoomFactory::createOne(['portal' => PortalFactory::createOne()])->getItemId());

        $document = self::getContainer()->get(RoomExportBuilder::class)->build($room)->content;

        // Internal bookkeeping, not room content.
        $this->assertArrayNotHasKey('activity', $document['room']);
        $this->assertArrayNotHasKey('extras', $document['room']);
        $this->assertArrayNotHasKey('status', $document['room']);
        $this->assertArrayNotHasKey('contactPersons', $document['room']);

        // Deleted entries are not exported at all, so the date has no business
        // being in the document.
        $this->assertArrayNotHasKey('deletionDate', $document['room']);

        // Who deleted an entry says something about a person and nothing about
        // content, and deleted entries are not exported anyway.
        $this->assertArrayNotHasKey('deleter', $document['room']);
    }

    /**
     * A missing creator yields null rather than crashing.
     */
    public function testAnEntryWithoutACreatorIsStillExported(): void
    {
        self::bootKernel();

        $room = $this->managedRoom(RoomFactory::createOne(['portal' => PortalFactory::createOne()])->getItemId());

        $document = self::getContainer()->get(RoomExportBuilder::class)->build($room)->content;

        $this->assertNull($document['room']['creator']);
        $this->assertNull($document['room']['modifier']);
        $this->assertSame($room->getItemId(), $document['room']['itemId'], 'the rest is there');
    }

    /**
     * A person on the row is rendered by UserNormalizer, not dumped.
     */
    public function testAPersonOnTheEntryIsRenderedTheExportWay(): void
    {
        self::bootKernel();

        $room = $this->managedRoom(RoomFactory::createOne(['portal' => PortalFactory::createOne()])->getItemId());

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $creator = $manager->getRepository(User::class)->findOneBy([]);
        self::assertNotNull($creator, 'the story leaves a room user behind to attribute this to');

        $room->setCreator($creator);
        $manager->flush();

        $document = self::getContainer()->get(RoomExportBuilder::class)->build($room)->content;

        $this->assertSame(
            ['id', 'name', 'email', 'roomStatus'],
            array_keys($document['room']['creator'])
        );
        $this->assertSame($creator->getItemId(), $document['room']['creator']['id']);
    }

    public function testAnArchivedRoomIsExportedAndSaysSo(): void
    {
        self::bootKernel();

        $room = $this->managedRoom(RoomFactory::createOne(['archived' => true, 'portal' => PortalFactory::createOne()])->getItemId());

        $document = self::getContainer()->get(RoomExportBuilder::class)->build($room)->content;

        $this->assertTrue($document['room']['archived']);
    }

    /**
     * The top level is fixed, and every rubric stands under its own name inside
     * `rubrics` — an importer walks them without knowing which ones exist.
     */
    public function testTheDocumentHasAFixedShape(): void
    {
        self::bootKernel();

        $room = $this->managedRoom(RoomFactory::createOne(['portal' => PortalFactory::createOne()])->getItemId());

        $document = self::getContainer()->get(RoomExportBuilder::class)->build($room)->content;

        $this->assertSame(['formatVersion', 'room', 'rubrics', 'files'], array_keys($document));

        // The section name is the rubric, spelled as `items.type` spells it.
        $this->assertArrayHasKey(RubricType::Announcement->value, $document['rubrics']);
    }

    /**
     * RoomFactory writes raw SQL, so its object is detached and its lifecycle
     * dates unset. Doctrine and the serializer need the managed row.
     */
    private function managedRoom(int $itemId): Room
    {
        return self::getContainer()->get(EntityManagerInterface::class)
            ->find(Room::class, $itemId);
    }
}
