<?php
namespace App\Tests\Database;

use App\Database\FixPhysicalFiles;
use App\Form\Model\File;
use App\Tests\UnitTester;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
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
            'get' => function() use ($filesDirectory) { return $filesDirectory; }
        ]);

        $this->tester->amInPath($filesDirectory);
    }

    protected function _after()
    {
        $filesDirectory = $this->tester->grabParameter('kernel.project_dir') . '/files_test';
        $this->tester->deleteDir($filesDirectory);
    }

    // tests
    public function testTopFolder()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        /**
         * Create top level folder test data
         * - 99 Server content
         * - Numeric folders for existing and non existing portals
         * - temp/
         * - Other non-numeric folders
         */
        mkdir('99');
        mkdir('12345');
        mkdir('23456');
        mkdir('34567');
        mkdir('temp');
        mkdir('somefolder');

        $fileA = $this->make(File::class, ['portalId' => 12345]);
        $fileB = $this->make(File::class, ['portalId' => 23456]);

        $queryBuilderStub = $this->make(QueryBuilder::class, [
            'execute' => [$fileA, $fileB]
        ]);

        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $queryBuilderStub,
        ]);

        $fix = new FixPhysicalFiles($connectionStub, $this->parameterBagStub);
        $this->assertTrue($fix->resolve($symfonyStyle));

        // Server and temp directory must remain
        $this->tester->assertDirectoryExists('99');
        $this->tester->assertDirectoryExists('temp');

        // Only the directories with existing portals must remain
        $this->tester->assertDirectoryExists('12345');
        $this->tester->assertDirectoryExists('23456');
        $this->tester->assertDirectoryDoesNotExist('34567');

        // Non-numeric folds must not remain
        $this->tester->assertDirectoryDoesNotExist('somefolder');
    }

    public function testRoomPath()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        /**
         * The second level (room part one) only contains numeric folders with a length of 4 digits
         * The third level will hold the remaining digits + '_'
         */
        mkdir('12345');
        mkdir('12345/somefolder');
        mkdir('12345/1234');
        mkdir('12345/1234/abc');
        mkdir('12345/1234/123');
        mkdir('12345/1234/123_');
        mkdir('12345/123');
        mkdir('12345/12345');

        $file = $this->make(File::class, ['filesId' => 1, 'contextId' => 1234123, 'portalId' => 12345]);

        $queryBuilderStub = $this->make(QueryBuilder::class, [
            'execute' => [$file]
        ]);

        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $queryBuilderStub,
        ]);

        $fix = new FixPhysicalFiles($connectionStub, $this->parameterBagStub);
        $this->assertTrue($fix->resolve($symfonyStyle));

        $this->tester->assertDirectoryExists('12345');
        $this->tester->assertDirectoryExists('12345/1234');
        $this->tester->assertDirectoryExists('12345/1234/123_');

        $this->tester->assertDirectoryDoesNotExist('12345/somefolder');
        $this->tester->assertDirectoryDoesNotExist('12345/123');
        $this->tester->assertDirectoryDoesNotExist('12345/12345');
        $this->tester->assertDirectoryDoesNotExist('12345/1234/abc');
        $this->tester->assertDirectoryDoesNotExist('12345/1234/123');
    }

    public function testRoomFolder()
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
        mkdir('12345/1234/123_/invalid.txt');
        mkdir('12345/1234/123_/1234.txt');
        mkdir('12345/1234/123_/cid1234123_bginfo_filename.jpg');
        mkdir('12345/1234/123_/cid1234123_logo_filename.jpg');
        mkdir('12345/1234/123_/cid1234123_user_filename.jpg');

        $file = $this->make(File::class, [
            'filesId' => 1234,
            'filename' => 'org_filename.txt',
            'contextId' => 1234123,
            'portalId' => 12345,
        ]);

        $queryBuilderStub = $this->make(QueryBuilder::class, [
            'execute' => [$file]
        ]);

        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $queryBuilderStub,
        ]);

        $fix = new FixPhysicalFiles($connectionStub, $this->parameterBagStub);
        $this->assertTrue($fix->resolve($symfonyStyle));

        $this->tester->assertFileExists('12345/1234/123_/1234.txt');
        $this->tester->assertFileExists('12345/1234/123_/cid1234123_bginfo_filename.jpg');
        $this->tester->assertFileExists('12345/1234/123_/cid1234123_logo_filename.jpg');
        $this->tester->assertFileExists('12345/1234/123_/cid1234123_user_filename.jpg');

        $this->tester->assertFileDoesNotExist('12345/1234/123_/invalid.txt');

        // TODO: Consider checking for non existing room or user id's in the cid-filename
    }
}