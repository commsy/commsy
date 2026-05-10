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

use App\Entity\Account;
use App\Entity\Lock;
use App\Repository\LockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
final class LockRepositoryTest extends KernelTestCase
{
    private LockRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(LockRepository::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSavePersistsLock(): void
    {
        $lock = $this->makeLock(91_001, 'tok-A');
        $this->repository->save($lock, flush: true);

        $reloaded = $this->repository->findOneBy(['itemId' => 91_001]);
        self::assertNotNull($reloaded);
        self::assertSame('tok-A', $reloaded->getToken());
    }

    public function testRemoveDeletesLock(): void
    {
        $lock = $this->makeLock(91_002, 'tok-B');
        $this->repository->save($lock, flush: true);
        $this->repository->remove($lock, flush: true);
        $this->em->clear();

        self::assertNull($this->repository->findOneBy(['itemId' => 91_002]));
    }

    public function testSaveWithoutFlushDoesNotPersistImmediately(): void
    {
        $lock = $this->makeLock(91_003, 'tok-C');
        $this->repository->save($lock, flush: false);
        $this->em->clear();

        self::assertNull($this->repository->findOneBy(['itemId' => 91_003]));
    }

    private function makeLock(int $itemId, string $token): Lock
    {
        $account = $this->em->getRepository(Account::class)
            ->find(AccountStory::get('account')->getId());

        return (new Lock())
            ->setAccount($account)
            ->setItemId($itemId)
            ->setToken($token);
    }
}
