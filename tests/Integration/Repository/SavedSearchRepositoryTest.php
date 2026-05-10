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

use App\Entity\SavedSearch;
use App\Repository\SavedSearchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SavedSearchRepositoryTest extends KernelTestCase
{
    private SavedSearchRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(SavedSearchRepository::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testGetSavedSearchesByAccountIdReturnsAccountSearches(): void
    {
        $accountId = 130_001;
        $own = $this->makeSavedSearch($accountId, 'mine');
        $other = $this->makeSavedSearch(130_002, 'theirs');

        $results = $this->repository->getSavedSearchesByAccountId($accountId);
        $ids = array_map(static fn (SavedSearch $s): int => $s->getId(), $results);

        self::assertContains($own->getId(), $ids);
        self::assertNotContains($other->getId(), $ids);
    }

    public function testGetSavedSearchesByAccountIdReturnsEmptyForUnknownAccount(): void
    {
        self::assertSame([], $this->repository->getSavedSearchesByAccountId(999_999));
    }

    public function testRemoveSavedSearchesByAccountIdDeletesAllOfThem(): void
    {
        $accountId = 130_010;
        $aId = $this->makeSavedSearch($accountId, 'a')->getId();
        $bId = $this->makeSavedSearch($accountId, 'b')->getId();
        $otherId = $this->makeSavedSearch(130_011, 'unrelated')->getId();

        $this->repository->removeSavedSearchesByAccountId($accountId);
        $this->em->clear();

        self::assertNull($this->em->getRepository(SavedSearch::class)->find($aId));
        self::assertNull($this->em->getRepository(SavedSearch::class)->find($bId));
        self::assertNotNull(
            $this->em->getRepository(SavedSearch::class)->find($otherId),
            'Searches of other accounts must NOT be touched',
        );
    }

    public function testRemoveSavedSearchDeletesTheSpecificRow(): void
    {
        $search = $this->makeSavedSearch(130_020, 'doomed');
        $id = $search->getId();

        $this->repository->removeSavedSearch($search);
        $this->em->clear();

        self::assertNull($this->em->getRepository(SavedSearch::class)->find($id));
    }

    private function makeSavedSearch(int $accountId, string $title): SavedSearch
    {
        $search = (new SavedSearch())
            ->setAccountId($accountId)
            ->setTitle($title)
            ->setSearchUrl('/search?q=' . $title);

        $this->em->persist($search);
        $this->em->flush();

        return $search;
    }
}
