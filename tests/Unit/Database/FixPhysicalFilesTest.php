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
use Symfony\Component\Routing\Annotation\Route;

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

    public function testStringPath()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        /**
         * The second level (room part one) only contains numeric folders with a length of 4 digits
         * The third level will hold the remaining digits + '_'
         */
        mkdir('temp');
        mkdir('portal');
        mkdir('someother');

        $file = $this->make(File::class, ['filesId' => 1, 'contextId' => 1234123, 'portalId' => 12345]);

        $queryBuilderStub = $this->make(QueryBuilder::class, [
            'execute' => [$file]
        ]);

        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $queryBuilderStub,
        ]);

        $fix = new FixPhysicalFiles($connectionStub, $this->parameterBagStub);
        $this->assertTrue($fix->resolve($symfonyStyle));

        $this->tester->assertDirectoryExists('temp');
        $this->tester->assertDirectoryExists('portal');

        $this->tester->assertDirectoryDoesNotExist('someother');
    }

    public function testRoomFolder()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        mkdir('12345');
        mkdir('12345/1234');
        mkdir('12345/1234/_123');
        mkdir('12345/1234/123');
        mkdir('12345/1234/invalid');

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

        $this->tester->assertDirectoryExists('12345');
        $this->tester->assertDirectoryExists('12345/1234');
        $this->tester->assertDirectoryExists('12345/1234/_123');

        $this->tester->assertDirectoryDoesNotExist('12345/1234/123');
        $this->tester->assertDirectoryDoesNotExist('12345/1234/invalid');
    }

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

        // TODO: Files has no 'portalId', but a 'contextId'
        $fileA = $this->make(File::class, ['portalId' => 12345]);
        $fileB = $this->make(File::class, ['portalId' => 23456]);

        $queryBuilderStub = $this->make(QueryBuilder::class, [
            'execute' => [$fileA, $fileB]
        ]);

        // If unit framework asks for files, those mocks will be returned
        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $queryBuilderStub,
        ]);

        $fix = new FixPhysicalFiles($connectionStub, $this->parameterBagStub);
        $this->assertTrue($fix->resolve($symfonyStyle));

        // Server and temp directory must remain
        $this->tester->assertDirectoryExists('99');
        $this->tester->assertDirectoryExists('temp');

        // Only the directories with existing portals must remain
        // TODO: Take care of userrooms!
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
        mkdir('12345/1234/_123');
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
        $this->tester->assertDirectoryExists('12345/1234/_123');

        $this->tester->assertDirectoryDoesNotExist('12345/somefolder');
        $this->tester->assertDirectoryDoesNotExist('12345/123');
        $this->tester->assertDirectoryDoesNotExist('12345/12345');
        $this->tester->assertDirectoryDoesNotExist('12345/1234/abc');
        $this->tester->assertDirectoryDoesNotExist('12345/1234/123');
    }

    public function testRoomPathFirstLevel()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        /**
         * The second level (room part one) only contains numeric folders with a length of 4 digits
         * The third level will hold the remaining digits + '_'
         */
        mkdir('12345');
        mkdir('12345/somefolder');
        mkdir('12345/1234');

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

        $this->tester->assertDirectoryDoesNotExist('12345/somefolder');
    }

    public function testFileNamingPattern()
    {
        $symfonyStyle = $this->makeEmpty(SymfonyStyle::class);

        /**
         * The last room level must only contain one of the following files:
         * - digit-only filename matching a file id with extension
         * - a user or room logo in the form of: cid[roomId]_bginfo|logo|[username]_[filename].[extension]
         */
        mkdir('12345');
        mkdir('12345/1234');
        mkdir('12345/1234/_123');
        touch('12345/1234/_123/1234.txt');
        touch('12345/1234/_123/invalid.txt');
        touch('12345/1234/_123/cid1234123_bginfo_filename.jpg');
        touch('12345/1234/_123/cid1234125_logo_filename.jpg');
        touch('12345/1234/_123/cid1234126_user_filename.jpg');

        $file = $this->make(File::class, [
            'filesId' => 1234123,
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

        $this->tester->assertFileExists('12345/1234/_123/1234.txt');
        $this->tester->assertFileExists('12345/1234/_123/cid1234123_bginfo_filename.jpg');
        $this->tester->assertFileExists('12345/1234/_123/cid1234125_logo_filename.jpg');
        $this->tester->assertFileExists('12345/1234/_123/cid1234126_user_filename.jpg');

        $this->tester->assertFileDoesNotExist('12345/1234/123_/invalid.txt');
    }
}