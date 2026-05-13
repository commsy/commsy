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

use App\Entity\License;
use App\Repository\LicenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LicenseRepositoryTest extends KernelTestCase
{
    private LicenseRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(LicenseRepository::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testFindByContextOrderByPositionReturnsLicensesInOrder(): void
    {
        $contextId = 80_001;
        $a = $this->makeLicense($contextId, 'A', position: 2);
        $b = $this->makeLicense($contextId, 'B', position: 0);
        $c = $this->makeLicense($contextId, 'C', position: 1);

        $results = $this->repository->findByContextOrderByPosition($contextId);
        $titles = array_map(static fn (License $l): string => $l->getTitle(), $results);

        self::assertSame(['B', 'C', 'A'], $titles);
    }

    public function testFindHighestPositionReturnsTheLargestPositionInTheContext(): void
    {
        $contextId = 80_010;
        $this->makeLicense($contextId, 'low', position: 0);
        $this->makeLicense($contextId, 'mid', position: 5);
        $this->makeLicense($contextId, 'high', position: 7);

        $rows = $this->repository->findHighestPosition($contextId);

        self::assertSame(7, (int) $rows[0]['position']);
    }

    public function testFindHighestPositionReturnsEmptyForUnknownContext(): void
    {
        self::assertSame([], $this->repository->findHighestPosition(999_999));
    }

    public function testUpdatePositionsRewritesPositionsInOrder(): void
    {
        $contextId = 80_020;
        $a = $this->makeLicense($contextId, 'A', position: 0);
        $b = $this->makeLicense($contextId, 'B', position: 1);
        $c = $this->makeLicense($contextId, 'C', position: 2);

        // New ordering: C, A, B → positions become 0, 1, 2.
        $this->repository->updatePositions(
            [['itemId' => $c->getId()], ['itemId' => $a->getId()], ['itemId' => $b->getId()]],
            $contextId,
        );
        $this->em->clear();

        $results = $this->repository->findByContextOrderByPosition($contextId);
        $titles = array_map(static fn (License $l): string => $l->getTitle(), $results);

        self::assertSame(['C', 'A', 'B'], $titles);
    }

    private function makeLicense(int $contextId, string $title, int $position): License
    {
        $license = new License();
        $license->setContextId($contextId);
        $license->setTitle($title);
        $license->setContent($title . '-content');
        $license->setPosition($position);

        $this->em->persist($license);
        $this->em->flush();

        return $license;
    }
}
