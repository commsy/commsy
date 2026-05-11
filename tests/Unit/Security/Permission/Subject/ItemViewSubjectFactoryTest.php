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

namespace Tests\Unit\Security\Permission\Subject;

use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\RoomRepository;
use App\Security\Permission\Subject\ItemViewSubjectFactory;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ItemViewSubjectFactoryTest extends TestCase
{
    private RoomRepository&MockObject $roomRepository;
    private ItemViewSubjectFactory $factory;

    protected function setUp(): void
    {
        $this->roomRepository = $this->createMock(RoomRepository::class);
        $this->factory = new ItemViewSubjectFactory($this->roomRepository);
    }

    public function testBuildsSubjectFromMaterials(): void
    {
        $creator = $this->user(itemId: 99);
        $item = $this->materials(itemId: 5, contextId: 42, activationDate: null);
        $item->setCreator($creator);

        $room = (new Room())->setItemId(42);
        $this->roomRepository->method('find')->with(42)->willReturn($room);

        $subject = $this->factory->fromItem($item);

        self::assertSame(5, $subject->itemId);
        self::assertSame(42, $subject->contextId);
        self::assertSame(99, $subject->creatorId);
        self::assertFalse($subject->isDeactivated);
        self::assertFalse($subject->contextIsDeleted);
    }

    public function testMarksFutureActivationDateAsDeactivated(): void
    {
        $item = $this->materials(itemId: 5, contextId: 42, activationDate: new DateTime('+1 day'));
        $room = (new Room())->setItemId(42);
        $this->roomRepository->method('find')->willReturn($room);

        $subject = $this->factory->fromItem($item);

        self::assertTrue($subject->isDeactivated);
    }

    public function testTreatsMissingContextRowAsContextDeleted(): void
    {
        $item = $this->materials(itemId: 5, contextId: 42, activationDate: null);
        $this->roomRepository->method('find')->willReturn(null);

        $subject = $this->factory->fromItem($item);

        self::assertTrue($subject->contextIsDeleted);
    }

    public function testReadsContextDeletionDateFromPreloadedRoomWithoutLookup(): void
    {
        $item = $this->materials(itemId: 5, contextId: 42, activationDate: null);
        $deletedRoom = (new Room())->setItemId(42)->setDeletionDate(new DateTime());

        $this->roomRepository->expects(self::never())->method('find');

        $subject = $this->factory->fromItem($item, $deletedRoom);

        self::assertTrue($subject->contextIsDeleted);
    }

    public function testFallsBackToLookupWhenPreloadedRoomMismatchesContextId(): void
    {
        $item = $this->materials(itemId: 5, contextId: 42, activationDate: null);
        $unrelatedRoom = (new Room())->setItemId(999);
        $actualRoom = (new Room())->setItemId(42);

        $this->roomRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($actualRoom);

        $subject = $this->factory->fromItem($item, $unrelatedRoom);

        self::assertFalse($subject->contextIsDeleted);
    }

    public function testNullContextIdProducesContextDeletedTrue(): void
    {
        $item = $this->materials(itemId: 5, contextId: null, activationDate: null);

        $this->roomRepository->expects(self::never())->method('find');

        $subject = $this->factory->fromItem($item);

        self::assertNull($subject->contextId);
        self::assertTrue($subject->contextIsDeleted);
    }

    private function materials(int $itemId, ?int $contextId, ?DateTime $activationDate): Materials
    {
        $m = new Materials();
        $idProp = new ReflectionProperty(Materials::class, 'itemId');
        $idProp->setValue($m, $itemId);
        if ($contextId !== null) {
            $m->setContextId($contextId);
        }
        if ($activationDate !== null) {
            $m->setActivationDate($activationDate);
        }
        return $m;
    }

    private function user(int $itemId): User
    {
        $u = new User();
        $u->itemId = $itemId;
        return $u;
    }
}
