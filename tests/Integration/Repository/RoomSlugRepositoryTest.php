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

use App\Entity\RoomSlug;
use App\Repository\RoomSlugRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Story\PortalStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(PortalStory::class)]
final class RoomSlugRepositoryTest extends KernelTestCase
{
    private RoomSlugRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(RoomSlugRepository::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSavePersistsTheEntity(): void
    {
        $room = $this->makeRoom();

        $slug = (new RoomSlug())
            ->setRoom($room)
            ->setSlug('my-test-slug');

        $this->repository->save($slug, flush: true);

        $reloaded = $this->em->getRepository(RoomSlug::class)
            ->findOneBy(['slug' => 'my-test-slug']);
        self::assertNotNull($reloaded);
        self::assertSame($room->getItemId(), $reloaded->getRoom()->getItemId());
    }

    public function testRemoveDeletesThePersistedEntity(): void
    {
        $slug = (new RoomSlug())
            ->setRoom($this->makeRoom())
            ->setSlug('to-be-removed');
        $this->repository->save($slug, flush: true);

        $this->repository->remove($slug, flush: true);
        $this->em->clear();

        self::assertNull(
            $this->em->getRepository(RoomSlug::class)->findOneBy(['slug' => 'to-be-removed']),
        );
    }

    public function testSaveWithoutFlushDoesNotPersistImmediately(): void
    {
        $slug = (new RoomSlug())
            ->setRoom($this->makeRoom())
            ->setSlug('not-flushed');

        $this->repository->save($slug, flush: false);

        // Without flush, the row isn't visible in a separate clear/find cycle.
        $this->em->clear();
        self::assertNull(
            $this->em->getRepository(RoomSlug::class)->findOneBy(['slug' => 'not-flushed']),
        );
    }

    /**
     * Foundry-created Rooms come back as detached proxies with respect
     * to the test's own EM — re-fetching by id gives us a managed entity
     * that ManyToOne(Room) can reference without the cascade-persist
     * complaint.
     */
    private function makeRoom(): \App\Entity\Room
    {
        $room = RoomFactory::createOne([
            'portal' => PortalStory::get('portal'),
            'type' => 'project',
        ]);
        return $this->em->getRepository(\App\Entity\Room::class)->find($room->getItemId());
    }
}
