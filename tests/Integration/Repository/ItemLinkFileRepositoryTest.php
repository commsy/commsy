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

use App\Repository\ItemLinkFileRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\FilesFactory;

final class ItemLinkFileRepositoryTest extends KernelTestCase
{
    private ItemLinkFileRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(ItemLinkFileRepository::class);
        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
    }

    public function testGetLinkedFileIdsReturnsActiveLinks(): void
    {
        $file = FilesFactory::createOne();
        $itemId = 60_001;
        $versionId = 1;

        $this->connection->insert('item_link_file', [
            'item_iid' => $itemId,
            'item_vid' => $versionId,
            'file_id' => $file->getFilesId(),
        ]);

        $ids = $this->repository->getLinkedFileIds($itemId, $versionId);

        self::assertSame([$file->getFilesId()], array_map('intval', $ids));
    }

    public function testGetLinkedFileIdsExcludesSoftDeletedLinks(): void
    {
        $file = FilesFactory::createOne();
        $itemId = 60_002;
        $versionId = 1;

        $this->connection->insert('item_link_file', [
            'item_iid' => $itemId,
            'item_vid' => $versionId,
            'file_id' => $file->getFilesId(),
            'deleter_id' => 1,
            'deletion_date' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        self::assertSame(
            [],
            $this->repository->getLinkedFileIds($itemId, $versionId),
            'soft-deleted links must be filtered',
        );
    }

    public function testGetLinkedFileIdsReturnsEmptyForUnknownItemVersion(): void
    {
        self::assertSame(
            [],
            $this->repository->getLinkedFileIds(999_991, 999),
        );
    }

    /**
     * An entry that loses an attachment keeps the old link on the old version.
     * Exporting the version an entry is shown at is what keeps a removed
     * attachment out of the archive.
     */
    public function testFindLinkedFilesAnswersForTheAskedForVersionOnly(): void
    {
        $dropped = FilesFactory::createOne();
        $kept = FilesFactory::createOne();
        $itemId = 60_010;

        $this->link($itemId, 1, $dropped->getFilesId());
        $this->link($itemId, 2, $kept->getFilesId());

        self::assertSame(
            [$kept->getFilesId()],
            array_keys($this->repository->findLinkedFiles([$itemId => 2]))
        );
    }

    /**
     * The per-item lookup is what legacy asks when it builds an entry's file
     * list. Reading a whole export in one go must not answer differently.
     */
    public function testFindLinkedFilesAgreesWithTheLookupLegacyUses(): void
    {
        $first = FilesFactory::createOne();
        $second = FilesFactory::createOne();
        $other = FilesFactory::createOne();

        $this->link(60_011, 3, $first->getFilesId());
        $this->link(60_011, 3, $second->getFilesId());
        $this->link(60_012, 0, $other->getFilesId());

        $batched = $this->repository->findLinkedFiles([60_011 => 3, 60_012 => 0]);

        $perItem = array_merge(
            array_map('intval', $this->repository->getLinkedFileIds(60_011, 3)),
            array_map('intval', $this->repository->getLinkedFileIds(60_012, 0))
        );

        sort($perItem);
        $found = array_keys($batched);
        sort($found);

        self::assertSame($perItem, $found);
    }

    public function testFindLinkedFilesCarriesAFileSharedByTwoEntriesOnce(): void
    {
        $shared = FilesFactory::createOne();

        $this->link(60_013, 0, $shared->getFilesId());
        $this->link(60_014, 0, $shared->getFilesId());

        self::assertCount(1, $this->repository->findLinkedFiles([60_013 => 0, 60_014 => 0]));
    }

    public function testFindLinkedFilesLeavesOutDeletedLinksAndDeletedFiles(): void
    {
        $unlinked = FilesFactory::createOne();
        $deleted = FilesFactory::createOne(['deletionDate' => new \DateTime(), 'deleterId' => 1]);

        $this->link(60_015, 0, $unlinked->getFilesId(), deleted: true);
        $this->link(60_015, 0, $deleted->getFilesId());

        self::assertSame([], $this->repository->findLinkedFiles([60_015 => 0]));
    }

    public function testFindLinkedFilesWithoutEntriesAsksNothing(): void
    {
        self::assertSame([], $this->repository->findLinkedFiles([]));
    }

    private function link(int $itemId, int $versionId, int $fileId, bool $deleted = false): void
    {
        $this->connection->insert('item_link_file', [
            'item_iid' => $itemId,
            'item_vid' => $versionId,
            'file_id' => $fileId,
            'deleter_id' => $deleted ? 1 : null,
            'deletion_date' => $deleted ? (new \DateTimeImmutable())->format('Y-m-d H:i:s') : null,
        ]);
    }
}
