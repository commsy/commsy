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

use App\Entity\Hash;
use App\Repository\HashRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class HashRepositoryTest extends KernelTestCase
{
    private HashRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(HashRepository::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateHashPersistsAndReturnsHash(): void
    {
        $hash = $this->repository->createHash(11_001);

        self::assertInstanceOf(Hash::class, $hash);
        self::assertSame(11_001, $hash->getUserId());
        self::assertNotEmpty($hash->getRss());
        self::assertNotEmpty($hash->getIcal());

        $this->em->clear();
        $reloaded = $this->repository->findByUserId(11_001);
        self::assertNotNull($reloaded);
    }

    public function testFindByUserIdReturnsNullWhenAbsent(): void
    {
        self::assertNull($this->repository->findByUserId(99_991));
    }

    public function testFindByRssHashLooksUpByRssToken(): void
    {
        $hash = $this->repository->createHash(11_002);

        $found = $this->repository->findByRssHash($hash->getRss());

        self::assertSame($hash->getUserId(), $found->getUserId());
    }

    public function testFindByRssHashThrowsWhenAbsent(): void
    {
        $this->expectException(NoResultException::class);
        $this->repository->findByRssHash('does-not-exist');
    }

    public function testFindByICalHashLooksUpByIcalToken(): void
    {
        $hash = $this->repository->createHash(11_003);

        $found = $this->repository->findByICalHash($hash->getIcal());

        self::assertSame($hash->getUserId(), $found->getUserId());
    }

    public function testDeleteHashRemovesTheRow(): void
    {
        $hash = $this->repository->createHash(11_004);
        $userId = $hash->getUserId();

        $this->repository->deleteHash($hash);
        $this->em->clear();

        self::assertNull($this->repository->findByUserId($userId));
    }

    public function testDeleteHashesByUserIdsBatchDeletes(): void
    {
        $h1 = $this->repository->createHash(11_005);
        $h2 = $this->repository->createHash(11_006);
        $survivor = $this->repository->createHash(11_007);

        $this->repository->deleteHashesByUserIds([11_005, 11_006]);
        $this->em->clear();

        self::assertNull($this->repository->findByUserId(11_005));
        self::assertNull($this->repository->findByUserId(11_006));
        self::assertNotNull($this->repository->findByUserId(11_007));
    }
}
