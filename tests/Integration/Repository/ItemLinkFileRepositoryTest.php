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
}
