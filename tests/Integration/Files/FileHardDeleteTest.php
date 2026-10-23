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

namespace Tests\Integration\Files;

use App\Files\FileDeleter;
use DateTime;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\FilesFactory;
use Zenstruck\Foundry\Test\Factories;

/**
 * Pins {@see FileDeleter::hardDeleteExpiredFiles()} on disk: the file the
 * download reads (`filepath`) goes together with its row, and so does an
 * image's thumbnail — but nothing outside the files directory, and nothing
 * another row still names.
 */
final class FileHardDeleteTest extends KernelTestCase
{
    use Factories;

    private const RETENTION_DAYS = 182;

    private Connection $connection;
    private FileDeleter $fileDeleter;
    private string $projectDir;
    private string $directory;

    /** @var string[] files created outside the test directory */
    private array $outside = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->connection = $container->get(Connection::class);
        $this->fileDeleter = $container->get(FileDeleter::class);
        $this->projectDir = (string) $container->getParameter('kernel.project_dir');
        $this->directory = $container->getParameter('files_directory') . '/phpunit-hard-delete/' . uniqid();
        (new Filesystem())->mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove([$this->directory, ...$this->outside]);

        parent::tearDown();
    }

    public function testTheFileAndItsThumbnailLeaveTheDiskWithTheirRow(): void
    {
        $path = $this->diskFile('1.png');
        $thumb = $this->diskFile('1.png_thumb');
        $fileId = $this->fileRow($path, deletedDaysAgo: 400);

        $this->fileDeleter->hardDeleteExpiredFiles(self::RETENTION_DAYS);

        self::assertFalse($this->connection->fetchOne('SELECT 1 FROM files WHERE files_id = ?', [$fileId]));
        self::assertFileDoesNotExist($path);
        self::assertFileDoesNotExist($thumb);
    }

    public function testAFileWithinTheRetentionStays(): void
    {
        $path = $this->diskFile('2.txt');
        $fileId = $this->fileRow($path, deletedDaysAgo: 10);

        $this->fileDeleter->hardDeleteExpiredFiles(self::RETENTION_DAYS);

        self::assertNotFalse($this->connection->fetchOne('SELECT 1 FROM files WHERE files_id = ?', [$fileId]));
        self::assertFileExists($path);
    }

    public function testADiskFileAnotherRowStillNamesStays(): void
    {
        $path = $this->diskFile('3.txt');
        $this->fileRow($path, deletedDaysAgo: 400);
        $this->fileRow($path, deletedDaysAgo: null);

        $this->fileDeleter->hardDeleteExpiredFiles(self::RETENTION_DAYS);

        self::assertFileExists($path);
    }

    public function testNothingOutsideTheFilesDirectoryIsRemoved(): void
    {
        $filesDirectory = (string) self::getContainer()->getParameter('files_directory');
        $outside = \dirname($filesDirectory) . '/phpunit-outside-' . uniqid() . '.txt';
        file_put_contents($outside, 'keep');
        $this->outside[] = $outside;

        // once by its plain path, once climbing out of the files directory
        $this->fileRow($outside, deletedDaysAgo: 400);
        $this->fileRow($filesDirectory . '/../' . basename($outside), deletedDaysAgo: 400);

        $this->fileDeleter->hardDeleteExpiredFiles(self::RETENTION_DAYS);

        self::assertFileExists($outside);
    }

    private function diskFile(string $name): string
    {
        $path = $this->directory . '/' . $name;
        file_put_contents($path, 'content');

        return $path;
    }

    /**
     * Stores the path the way uploads do: relative to the project directory.
     */
    private function fileRow(string $absolutePath, ?int $deletedDaysAgo): int
    {
        $fileId = FilesFactory::createOne([
            'filepath' => ltrim(substr($absolutePath, \strlen($this->projectDir)), '/'),
        ])->getFilesId();

        if ($deletedDaysAgo !== null) {
            $this->connection->executeStatement(
                'UPDATE files SET deletion_date = ? WHERE files_id = ?',
                [(new DateTime("-$deletedDaysAgo days"))->format('Y-m-d H:i:s'), $fileId]
            );
        }

        return $fileId;
    }
}
