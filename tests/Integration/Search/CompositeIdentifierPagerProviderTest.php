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

namespace Tests\Integration\Search;

use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use FOS\ElasticaBundle\Provider\PagerProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins that the material index can be paged at all, and that paging it in
 * slices loses nothing.
 *
 * `Materials` is keyed by (item_id, version_id). The bundle's own provider
 * pages with fetch-join pagination, which resolves a page over
 * `WHERE <id> IN (…)` and therefore needs a single identifier —
 * `fos:elastica:populate --index=commsy_material` died on that with
 * `MappingException: Single id is not allowed on composite primary key`.
 * Nothing in the suite noticed, because indexing is never exercised by a
 * test (`.env.test` points Elasticsearch at an invalid host on purpose).
 *
 * This test stays below that line: it asks the provider for a pager and
 * reads the pages, without an Elasticsearch connection.
 */
final class CompositeIdentifierPagerProviderTest extends KernelTestCase
{
    private const PAGE_SIZE = 2;

    private Room $room;
    private User $roomUser;

    #[WithStory(RoomWithMemberStory::class)]
    public function testMaterialIndexCanBePaged(): void
    {
        $this->createMaterials(1);

        $pager = $this->pager();

        self::assertGreaterThan(0, $pager->getNbResults());
    }

    /**
     * The property that `fetchJoinCollection: false` rests on: one row per
     * object, so a slice holds exactly its objects. A fetch-joined
     * collection would multiply rows and push objects off the end of a
     * page — this comparison catches that.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testPagingInSlicesYieldsEveryMaterialExactlyOnce(): void
    {
        $created = $this->createMaterials(5);

        $pager = $this->pager();
        $pager->setMaxPerPage(self::PAGE_SIZE);

        $seen = [];
        for ($page = 1; $page <= $pager->getNbPages(); ++$page) {
            $pager->setCurrentPage($page);
            foreach ($pager->getCurrentPageResults() as $material) {
                self::assertInstanceOf(Materials::class, $material);
                $seen[] = $material->getItemId();
            }
        }

        self::assertSame(
            $pager->getNbResults(),
            count($seen),
            'every result must appear on exactly one page'
        );
        self::assertSame(array_unique($seen), $seen, 'no material may appear twice');

        foreach ($created as $itemId) {
            self::assertContains($itemId, $seen);
        }
    }

    /**
     * Materials are versioned and the query is supposed to hand out the
     * latest version of each entry, not one row per version.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testOnlyTheLatestVersionOfAMaterialIsPaged(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        $itemId = $material->getItemId();

        // A second version of the same entry, straight into the table.
        self::getContainer()->get('doctrine')->getConnection()->executeStatement(
            'INSERT INTO materials (item_id, version_id, context_id, creator_id, creation_date, title, public)
                SELECT item_id, version_id + 1, context_id, creator_id, NOW(), title, public
                FROM materials WHERE item_id = :itemId AND version_id = :versionId',
            ['itemId' => $itemId, 'versionId' => $material->getVersionId()]
        );

        $pager = $this->pager();
        $pager->setMaxPerPage(100);
        $pager->setCurrentPage(1);

        $versionsOfOurMaterial = [];
        foreach ($pager->getCurrentPageResults() as $paged) {
            if ($paged->getItemId() === $itemId) {
                $versionsOfOurMaterial[] = $paged->getVersionId();
            }
        }

        self::assertCount(1, $versionsOfOurMaterial, 'one row per entry, not per version');
        self::assertSame(
            $material->getVersionId() + 1,
            $versionsOfOurMaterial[0],
            'and it has to be the latest one'
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
    }

    /** @return int[] the created item ids */
    private function createMaterials(int $count): array
    {
        $itemIds = [];
        for ($i = 0; $i < $count; ++$i) {
            $itemIds[] = MaterialFactory::createOne([
                'room' => $this->room,
                'creator' => $this->roomUser,
            ])->getItemId();
        }

        return $itemIds;
    }

    private function pager(): \FOS\ElasticaBundle\Provider\PagerInterface
    {
        /** @var PagerProviderInterface $provider */
        $provider = self::getContainer()->get('fos_elastica.pager_provider.commsy_material');

        return $provider->provide();
    }
}
