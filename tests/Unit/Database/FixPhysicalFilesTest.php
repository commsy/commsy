<?php

namespace App\Tests\Database;

use App\Database\FixPhysicalFiles;
use App\Entity\Portal;
use App\Repository\FilesRepository;
use App\Repository\ItemRepository;
use App\Repository\PortalRepository;
use App\Repository\RoomRepository;
use App\Repository\ZzzFilesRepository;
use App\Repository\ZzzItemRepository;
use App\Repository\ZzzRoomRepository;
use App\Tests\UnitTester;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class FixPhysicalFilesTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    private ParameterBagInterface $parameterBagStub;

    protected function _before()
    {
        $filesDirectory = $this->tester->grabParameter('kernel.project_dir') . '/files_test';
        if (!is_dir($filesDirectory)) {
            mkdir($filesDirectory);
        }
        $this->tester->cleanDir($filesDirectory);

        $this->parameterBagStub = $this->makeEmpty(ParameterBagInterface::class, [
            'get' => function () use ($filesDirectory) {
                return $filesDirectory;
            }
        ]);

        $this->tester->amInPath($filesDirectory);
    }

    protected function _after()
    {
        $filesDirectory = $this->tester->grabParameter('kernel.project_dir') . '/files_test';
        $this->tester->deleteDir($filesDirectory);
    }

    // tests
    public function testFirstLevelFolder()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        /**
         * Create top level folder files_test
         * - 99 Server content
         * - Numeric folders for existing and non existing portals / project rooms
         * - temp/
         * - Other non-numeric folders
         */
        mkdir('99');
        mkdir('12345');
        mkdir('22222');
        mkdir('33333');
        mkdir('temp');
        mkdir('somefolder');

        $portalRepository = $this->makeEmpty(PortalRepository::class, [
            'findAll' => [
                $this->make(Portal::class, ['id' => 12345]),
            ],
        ]);
        $roomRepository = $this->makeEmpty(RoomRepository::class, [
            'getProjectAndUserRoomIds' => [22222],
        ]);

        $fix = new FixPhysicalFiles(
            $this->parameterBagStub,
            $portalRepository,
            $roomRepository,
            $this->makeEmpty(ZzzRoomRepository::class),
            $this->makeEmpty(FilesRepository::class),
            $this->makeEmpty(ZzzFilesRepository::class),
            $this->makeEmpty(ItemRepository::class),
            $this->makeEmpty(ZzzItemRepository::class),
            $this->makeEmpty(LoggerInterface::class)
        );
        $this->assertTrue($fix->resolve($symfonyStyle));

        // Server and temp directory must remain
        $this->tester->assertDirectoryExists('99');
        $this->tester->assertDirectoryExists('temp');

        // Only the directories with existing portals must remain
        $this->tester->assertDirectoryExists('12345');
        $this->tester->assertDirectoryExists('22222');
        $this->tester->assertDirectoryDoesNotExist('33333');

        // Non-numeric folds must not remain
        $this->tester->assertDirectoryDoesNotExist('somefolder');
    }


    public function testSecondLevelFolder()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        /**
         * The second level (room part one) only contains numeric folders with a length of 4 digits
         * The third level will hold the remaining digits + '_'
         */
        mkdir('12345');
        mkdir('12345/somefolder');
        mkdir('12345/123');
        mkdir('12345/1234');
        mkdir('12345/12345');

        $portalRepository = $this->makeEmpty(PortalRepository::class, [
            'findAll' => [
                $this->make(Portal::class, ['id' => 12345]),
            ],
        ]);

        $fix = new FixPhysicalFiles(
            $this->parameterBagStub,
            $portalRepository,
            $this->makeEmpty(RoomRepository::class),
            $this->makeEmpty(ZzzRoomRepository::class),
            $this->makeEmpty(FilesRepository::class),
            $this->makeEmpty(ZzzFilesRepository::class),
            $this->makeEmpty(ItemRepository::class),
            $this->makeEmpty(ZzzItemRepository::class),
            $this->makeEmpty(LoggerInterface::class)
        );
        $this->assertTrue($fix->resolve($symfonyStyle));

        $this->tester->assertDirectoryExists('12345');
        $this->tester->assertDirectoryExists('12345/1234');

        $this->tester->assertDirectoryDoesNotExist('12345/somefolder');
        $this->tester->assertDirectoryDoesNotExist('12345/123');
        $this->tester->assertDirectoryDoesNotExist('12345/12345');
    }

    public function testThirdLevelFolder()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        /**
         * The second level (room part one) only contains numeric folders with a length of 4 digits
         * The third level will hold the remaining digits + '_'
         */
        mkdir('12345');
        mkdir('12345/1234');
        mkdir('12345/1234/abc');
        mkdir('12345/1234/123');
        mkdir('12345/1234/_123');
        mkdir('12345/1234/123_');
        mkdir('12345/1234/888_');
        mkdir('12345/1234/999_');

        $portalRepository = $this->makeEmpty(PortalRepository::class, [
            'findAll' => [
                $this->make(Portal::class, ['id' => 12345]),
            ],
        ]);
        $roomRepository = $this->makeEmpty(RoomRepository::class);
        $zzzRoomRepository = $this->makeEmpty(ZzzRoomRepository::class);
        $filesRepository = $this->makeEmpty(FilesRepository::class, [
            'getNumFiles' => function (int $fileId, int $contextId) {
                return $contextId == 1234123 ? 1 : 0;
            },
        ]);
        $zzzFilesRepository = $this->makeEmpty(ZzzFilesRepository::class);
        $itemRepository = $this->makeEmpty(ItemRepository::class, [
            'getNumItems' => function (int $itemId) {
                return ($itemId == 1234123 || $itemId == 1234888) ? 1 : 0;
            },
        ]);
        $zzzItemRepository = $this->makeEmpty(ZzzItemRepository::class);

        $fix = new FixPhysicalFiles(
            $this->parameterBagStub,
            $portalRepository,
            $roomRepository,
            $zzzRoomRepository,
            $filesRepository,
            $zzzFilesRepository,
            $itemRepository,
            $zzzItemRepository,
            $this->makeEmpty(LoggerInterface::class)
        );
        $this->assertTrue($fix->resolve($symfonyStyle));

        $this->tester->assertDirectoryExists('12345');
        $this->tester->assertDirectoryExists('12345/1234');
        $this->tester->assertDirectoryExists('12345/1234/123_');

        // Make sure folder is not deleted even if the files table does not contain any files for the context.
        // Otherwise, we would also delete the autogenerated ones.
        $this->tester->assertDirectoryExists('12345/1234/888_');

        $this->tester->assertDirectoryDoesNotExist('12345/1234/abc');
        $this->tester->assertDirectoryDoesNotExist('12345/1234/123');
        $this->tester->assertDirectoryDoesNotExist('12345/1234/_123');
        $this->tester->assertDirectoryDoesNotExist('12345/1234/999_');
    }

    public function testFileLevel()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        /**
         * The last room level must only contain one of the following files:
         * - digit-only filename matching a file id with extension
         * - a user or room logo in the form of: cid[roomId]_bginfo|logo|[username]_[filename].[extension]
         */
        mkdir('12345');
        mkdir('12345/1234');
        mkdir('12345/1234/123_');
        touch('12345/1234/123_/no_extension');
        touch('12345/1234/123_/1234.txt');
        touch('12345/1234/123_/8888.txt');
        touch('12345/1234/123_/invalid.txt');
        touch('12345/1234/123_/cid1234123_bginfo_filename.jpg');
        touch('12345/1234/123_/cid1234125_logo_filename.jpg');
        touch('12345/1234/123_/cid1234126_user_filename.jpg');

        $portalRepository = $this->makeEmpty(PortalRepository::class, [
            'findAll' => [
                $this->make(Portal::class, ['id' => 12345]),
            ],
        ]);
        $filesRepository = $this->makeEmpty(FilesRepository::class, [
            'getNumFiles' => function (int $fileId, int $contextId) {
                return ($fileId == 1234 && $contextId == 1234123) ? 1 : 0;
            },
        ]);
        $itemRepository = $this->makeEmpty(ItemRepository::class, [
            'getNumItems' => function () {
                return 1;
            },
        ]);

        $fix = new FixPhysicalFiles(
            $this->parameterBagStub,
            $portalRepository,
            $this->makeEmpty(RoomRepository::class),
            $this->makeEmpty(ZzzRoomRepository::class),
            $filesRepository,
            $this->makeEmpty(ZzzFilesRepository::class),
            $itemRepository,
            $this->makeEmpty(ZzzItemRepository::class),
            $this->makeEmpty(LoggerInterface::class)
        );
        $this->assertTrue($fix->resolve($symfonyStyle));

        $this->tester->assertFileExists('12345/1234/123_/1234.txt');
        $this->tester->assertFileExists('12345/1234/123_/cid1234123_bginfo_filename.jpg');
        $this->tester->assertFileExists('12345/1234/123_/cid1234125_logo_filename.jpg');
        $this->tester->assertFileExists('12345/1234/123_/cid1234126_user_filename.jpg');

        $this->tester->assertFileDoesNotExist('12345/1234/123_/8888.txt');
        $this->tester->assertFileDoesNotExist('12345/1234/123_/no_extension');
        $this->tester->assertFileDoesNotExist('12345/1234/123_/invalid.txt');
    }
}