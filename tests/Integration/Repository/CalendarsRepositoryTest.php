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

namespace Tests\Integration\Repository;

use App\Entity\Room;
use App\Repository\CalendarsRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\CalendarsFactory;
use Tests\Factory\RoomFactory;
use Tests\Story\PortalStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(PortalStory::class)]
final class CalendarsRepositoryTest extends KernelTestCase
{
    private CalendarsRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(CalendarsRepository::class);
    }

    public function testFindByRoomIdReturnsCalendarsForThatRoom(): void
    {
        /** @var Room $room */
        $room = RoomFactory::createOne([
            'portal' => PortalStory::get('portal'),
            'type' => 'project',
        ]);

        $calendar = CalendarsFactory::createOne(['room' => $room]);

        $results = $this->repository->findByRoomId($room->getItemId());

        $ids = array_map(static fn ($c) => $c->getId(), $results);
        self::assertContains($calendar->getId(), $ids);
    }

    public function testFindByRoomIdReturnsEmptyForUnknownRoom(): void
    {
        self::assertSame([], $this->repository->findByRoomId(999_999));
    }
}
