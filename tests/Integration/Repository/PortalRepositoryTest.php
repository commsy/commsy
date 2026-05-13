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

use App\Entity\Portal;
use App\Repository\PortalRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;

final class PortalRepositoryTest extends KernelTestCase
{
    private PortalRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(PortalRepository::class);
    }

    public function testFindPortalByIdAcceptsPortalId(): void
    {
        $portal = PortalFactory::createOne();

        $found = $this->repository->findPortalById($portal->getId());

        self::assertInstanceOf(Portal::class, $found);
        self::assertSame($portal->getId(), $found->getId());
    }

    public function testFindPortalByIdResolvesViaRoomId(): void
    {
        $portal = PortalFactory::createOne();
        $room = RoomFactory::createOne([
            'portal' => $portal,
            'type' => 'project',
        ]);

        $found = $this->repository->findPortalById($room->getItemId());

        self::assertInstanceOf(Portal::class, $found);
        self::assertSame($portal->getId(), $found->getId());
    }

    public function testFindPortalByIdReturnsNullForUnknownId(): void
    {
        self::assertNull($this->repository->findPortalById(999_999));
    }

    public function testFindActivePortalsExcludesSoftDeleted(): void
    {
        $alive = PortalFactory::createOne();
        $deletedPortal = PortalFactory::createOne();

        // Soft-delete via Doctrine: Portal accepts setDeletionDate.
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $deletedPortal->setDeletionDate(new DateTime());
        $em->flush();

        $portals = $this->repository->findActivePortals();
        $ids = array_map(static fn (Portal $p): int => $p->getId(), $portals);

        self::assertContains($alive->getId(), $ids);
        self::assertNotContains($deletedPortal->getId(), $ids);
    }

    public function testFindActivePortalLooksUpById(): void
    {
        $portal = PortalFactory::createOne();

        $found = $this->repository->findActivePortal($portal->getId());
        self::assertSame($portal->getId(), $found?->getId());
    }

    public function testFindActivePortalReturnsNullForSoftDeleted(): void
    {
        $portal = PortalFactory::createOne();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $portal->setDeletionDate(new DateTime());
        $em->flush();

        self::assertNull($this->repository->findActivePortal($portal->getId()));
    }

    public function testFindAllActiveBehavesLikeFindActivePortals(): void
    {
        $portal = PortalFactory::createOne();

        $portals = $this->repository->findAllActive();
        $ids = array_map(static fn (Portal $p): int => $p->getId(), $portals);

        self::assertContains($portal->getId(), $ids);
    }
}
