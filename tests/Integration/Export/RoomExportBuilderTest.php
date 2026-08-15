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
use App\Export\RoomExportBuilder;
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

        $document = self::getContainer()->get(RoomExportBuilder::class)->build($room);

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

        $document = self::getContainer()->get(RoomExportBuilder::class)->build($room);

        // Internal bookkeeping, not room content.
        $this->assertArrayNotHasKey('activity', $document['room']);
        $this->assertArrayNotHasKey('extras', $document['room']);
        $this->assertArrayNotHasKey('status', $document['room']);
        $this->assertArrayNotHasKey('contactPersons', $document['room']);

        // Deleted entries are not exported at all, so the date has no business
        // being in the document.
        $this->assertArrayNotHasKey('deletionDate', $document['room']);

        // Personal data reaches the document through creator and modifier of the
        // entries, assembled by a normalizer — not by serializing the relation.
        $this->assertArrayNotHasKey('creator', $document['room']);
        $this->assertArrayNotHasKey('modifier', $document['room']);
    }

    public function testAnArchivedRoomIsExportedAndSaysSo(): void
    {
        self::bootKernel();

        $room = $this->managedRoom(RoomFactory::createOne(['archived' => true, 'portal' => PortalFactory::createOne()])->getItemId());

        $document = self::getContainer()->get(RoomExportBuilder::class)->build($room);

        $this->assertTrue($document['room']['archived']);
    }

    /**
     * RoomFactory writes its rows with raw SQL, so the object it returns is
     * detached and its lifecycle-filled dates were never set. Anything that
     * hands the room to Doctrine or to the serializer needs the managed row.
     */
    private function managedRoom(int $itemId): Room
    {
        return self::getContainer()->get(EntityManagerInterface::class)
            ->find(Room::class, $itemId);
    }
}
