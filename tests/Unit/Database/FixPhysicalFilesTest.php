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

namespace Tests\Unit\Database;

use App\Database\FixPhysicalFiles;
use App\Entity\Portal;
use App\Repository\FilesRepository;
use App\Repository\ItemRepository;
use App\Repository\PortalRepository;
use App\Repository\RoomRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

final class FixPhysicalFilesTest extends TestCase
{
    private ParameterBagInterface $parameterBagStub;

    private const string FILES_FOLDER = '/tmp/files_test';

    protected function setUp(): void
    {
        $filesystem = new Filesystem();
        if ($filesystem->exists(self::FILES_FOLDER)) {
            $filesystem->remove(self::FILES_FOLDER);
        }

        $filesystem->mkdir(self::FILES_FOLDER);

        $this->parameterBagStub = $this->createConfiguredMock(ParameterBagInterface::class, [
            'get' => self::FILES_FOLDER,
        ]);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        if ($filesystem->exists(self::FILES_FOLDER)) {
            $filesystem->remove(self::FILES_FOLDER);
        }
    }

    private function makeDir(string $folder): void
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir(self::FILES_FOLDER . '/' . $folder);
    }

    private function touch(string $file): void
    {
        $filesystem = new Filesystem();
        $filesystem->touch(self::FILES_FOLDER . '/' . $file);
    }

    // tests
    public function testFirstLevelFolder(): void
    {
        $symfonyStyle = $this->createStub(SymfonyStyle::class);

        /**
         * Create top level folder files_test
         * - 99 Server content
         * - Numeric folders for existing and non existing portals / project rooms
         * - temp/
         * - Other non-numeric folders
         */
        $this->makeDir('99');
        $this->makeDir('12345');
        $this->makeDir('22222');
        $this->makeDir('33333');
        $this->makeDir('temp');
        $this->makeDir('somefolder');

        $portalRepository = $this->createConfiguredMock(PortalRepository::class, [
            'findAll' => [
                $this->createConfiguredMock(Portal::class, ['getId' => 12345]),
            ],
        ]);
        $roomRepository = $this->createConfiguredMock(RoomRepository::class, [
            'getProjectAndUserRoomIds' => [22222],
        ]);

        $fix = new FixPhysicalFiles(
            $this->parameterBagStub,
            $portalRepository,
            $roomRepository,
            $this->createStub(FilesRepository::class),
            $this->createStub(ItemRepository::class),
            $this->createStub(LoggerInterface::class)
        );
        $this->assertTrue($fix->resolve($symfonyStyle));

        // Server and temp directory must remain
        $this->assertDirectoryExists(self::FILES_FOLDER . '/99');
        $this->assertDirectoryExists(self::FILES_FOLDER . '/temp');

        // Only the directories with existing portals must remain
        $this->assertDirectoryExists(self::FILES_FOLDER . '/12345');
        $this->assertDirectoryExists(self::FILES_FOLDER . '/22222');
        $this->assertDirectoryDoesNotExist(self::FILES_FOLDER . '/33333');

        // Non-numeric folds must not remain
        $this->assertDirectoryDoesNotExist(self::FILES_FOLDER . '/somefolder');
    }


    public function testSecondLevelFolder(): void
    {
        $symfonyStyle = $this->createStub(SymfonyStyle::class);

        /**
         * The second level (room part one) only contains numeric folders with a length of 4 digits
         * The third level will hold the remaining digits + '_'
         */
        $this->makeDir('12345');
        $this->makeDir('12345/somefolder');
        $this->makeDir('12345/123');
        $this->makeDir('12345/1234');
        $this->makeDir('12345/12345');

        $portalRepository = $this->createConfiguredMock(PortalRepository::class, [
            'findAll' => [
                $this->createConfiguredMock(Portal::class, ['getId' => 12345]),
            ],
        ]);

        $fix = new FixPhysicalFiles(
            $this->parameterBagStub,
            $portalRepository,
            $this->createStub(RoomRepository::class),
            $this->createStub(FilesRepository::class),
            $this->createStub(ItemRepository::class),
            $this->createStub(LoggerInterface::class)
        );
        $this->assertTrue($fix->resolve($symfonyStyle));

        $this->assertDirectoryExists(self::FILES_FOLDER . '/12345');
        $this->assertDirectoryExists(self::FILES_FOLDER . '/12345/1234');

        $this->assertDirectoryDoesNotExist(self::FILES_FOLDER . '/12345/somefolder');
        $this->assertDirectoryDoesNotExist(self::FILES_FOLDER . '/12345/123');
        $this->assertDirectoryDoesNotExist(self::FILES_FOLDER . '/12345/12345');
    }

    public function testThirdLevelFolder(): void
    {
        $symfonyStyle = $this->createStub(SymfonyStyle::class);

        /**
         * The second level (room part one) only contains numeric folders with a length of 4 digits
         * The third level will hold the remaining digits + '_'
         */
        $this->makeDir('12345');
        $this->makeDir('12345/1234');
        $this->makeDir('12345/1234/abc');
        $this->makeDir('12345/1234/123');
        $this->makeDir('12345/1234/_123');
        $this->makeDir('12345/1234/123_');
        $this->makeDir('12345/1234/888_');
        $this->makeDir('12345/1234/999_');

        $portalRepository = $this->createConfiguredMock(PortalRepository::class, [
            'findAll' => [
                $this->createConfiguredMock(Portal::class, ['getId' => 12345]),
            ],
        ]);
        $roomRepository = $this->createStub(RoomRepository::class);

        $filesRepository = $this->createStub(FilesRepository::class);
        $filesRepository->method('getNumFiles')
            ->willReturnCallback(fn(int $fileId, int $contextId) => $contextId == 1_234_123 ? 1 : 0);

        $itemRepository = $this->createStub(ItemRepository::class);
        $itemRepository->method('getNumItems')
            ->willReturnCallback(fn(int $itemId) => ($itemId == 1_234_123 || $itemId == 1_234_888) ? 1 : 0);

        $fix = new FixPhysicalFiles(
            $this->parameterBagStub,
            $portalRepository,
            $roomRepository,
            $filesRepository,
            $itemRepository,
            $this->createStub(LoggerInterface::class)
        );
        $this->assertTrue($fix->resolve($symfonyStyle));

        $this->assertDirectoryExists(self::FILES_FOLDER . '/12345');
        $this->assertDirectoryExists(self::FILES_FOLDER . '/12345/1234');
        $this->assertDirectoryExists(self::FILES_FOLDER . '/12345/1234/123_');

        // Make sure folder is not deleted even if the files table does not contain any files for the context.
        // Otherwise, we would also delete the autogenerated ones.
        $this->assertDirectoryExists(self::FILES_FOLDER . '/12345/1234/888_');

        $this->assertDirectoryDoesNotExist(self::FILES_FOLDER . '/12345/1234/abc');
        $this->assertDirectoryDoesNotExist(self::FILES_FOLDER . '/12345/1234/123');
        $this->assertDirectoryDoesNotExist(self::FILES_FOLDER . '/12345/1234/_123');
        $this->assertDirectoryDoesNotExist(self::FILES_FOLDER . '/12345/1234/999_');
    }

    public function testFileLevel(): void
    {
        $symfonyStyle = $this->createStub(SymfonyStyle::class);

        /**
         * The last room level must only contain one of the following files:
         * - digit-only filename matching a file id with extension
         * - a user or room logo in the form of: cid[roomId]_bginfo|logo|[username]_[filename].[extension]
         */
        $this->makeDir('12345');
        $this->makeDir('12345/1234');
        $this->makeDir('12345/1234/123_');
        $this->touch('12345/1234/123_/no_extension');
        $this->touch('12345/1234/123_/1234.txt');
        $this->touch('12345/1234/123_/8888.txt');
        $this->touch('12345/1234/123_/invalid.txt');
        $this->touch('12345/1234/123_/cid1234123_bginfo_filename.jpg');
        $this->touch('12345/1234/123_/cid1234125_logo_filename.jpg');
        $this->touch('12345/1234/123_/cid1234126_user_filename.jpg');

        $portalRepository = $this->createConfiguredMock(PortalRepository::class, [
            'findAll' => [
                $this->createConfiguredMock(Portal::class, ['getId' => 12345]),
            ],
        ]);

        $filesRepository = $this->createStub(FilesRepository::class);
        $filesRepository->method('getNumFiles')
            ->willReturnCallback(fn(int $fileId, int $contextId) => ($fileId == 1234 && $contextId == 1_234_123) ? 1 : 0);

        $itemRepository = $this->createConfiguredMock(ItemRepository::class, [
            'getNumItems' => 1,
        ]);

        $fix = new FixPhysicalFiles(
            $this->parameterBagStub,
            $portalRepository,
            $this->createStub(RoomRepository::class),
            $filesRepository,
            $itemRepository,
            $this->createStub(LoggerInterface::class)
        );
        $this->assertTrue($fix->resolve($symfonyStyle));

        $this->assertFileExists(self::FILES_FOLDER . '/12345/1234/123_/1234.txt');
        $this->assertFileExists(self::FILES_FOLDER . '/12345/1234/123_/cid1234123_bginfo_filename.jpg');
        $this->assertFileExists(self::FILES_FOLDER . '/12345/1234/123_/cid1234125_logo_filename.jpg');
        $this->assertFileExists(self::FILES_FOLDER . '/12345/1234/123_/cid1234126_user_filename.jpg');

        $this->assertFileDoesNotExist(self::FILES_FOLDER . '/12345/1234/123_/8888.txt');
        $this->assertFileDoesNotExist(self::FILES_FOLDER . '/12345/1234/123_/no_extension');
        $this->assertFileDoesNotExist(self::FILES_FOLDER . '/12345/1234/123_/invalid.txt');
    }
}
