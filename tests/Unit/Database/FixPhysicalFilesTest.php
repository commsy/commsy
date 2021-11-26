<?php
namespace App\Tests\Database;

use App\Database\FixPhysicalFiles;
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

        $fileA = ['portalId' => 11111];
        $fileB = ['portalId' => 22222];
        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $this->make(QueryBuilder::class, [
                'execute' => [$fileA, $fileB]
            ]),
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

        $fileA = ['portalId' => 12345];
        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $this->make(QueryBuilder::class, [
                'execute' => [$fileA]
            ]),
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
         * Create top level folder files_test
         * - 99 Server content
         * - Numeric folders for existing and non existing portals
         * - temp/
         * - Other non-numeric folders
         */
        mkdir('99');
        mkdir('11111');
        mkdir('22222');
        mkdir('33333');
        mkdir('temp');
        mkdir('somefolder');

        $fileA = ['portalId' => 11111];
        $fileB = ['portalId' => 22222];
        $fileC = ['portalId' => 99];
        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $this->make(QueryBuilder::class, [
                'execute' => [$fileA, $fileB, $fileC]
            ]),
        ]);

        $fix = new FixPhysicalFiles($connectionStub, $this->parameterBagStub);
        $this->assertTrue($fix->resolve($symfonyStyle));

        // Server and temp directory must remain
        $this->tester->assertDirectoryExists('99');
        $this->tester->assertDirectoryExists('temp');

        // Only the directories with existing portals must remain
        $this->tester->assertDirectoryExists('11111');
        $this->tester->assertDirectoryExists('22222');
        $this->tester->assertDirectoryDoesNotExist('33333');

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

        $fileA = ['portalId' => 12345];
        $fileB = ['portalId' => 22222];
        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $this->make(QueryBuilder::class, [
                'execute' => [$fileA, $fileB]
            ]),
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

        $fileA = ['portalId' => 12345];
        $fileB = ['portalId' => 22222];
        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $this->make(QueryBuilder::class, [
                'execute' => [$fileA, $fileB]
            ]),
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
        touch('12345/1234/_123/no_extension');
        touch('12345/1234/_123/1234.txt');
        touch('12345/1234/_123/invalid.txt');
        touch('12345/1234/_123/cid1234123_bginfo_filename.jpg');
        touch('12345/1234/_123/cid1234125_logo_filename.jpg');
        touch('12345/1234/_123/cid1234126_user_filename.jpg');

        $fileA = ['portalId' => 12345];
        $fileB = ['portalId' => 22222];
        $connectionStub = $this->makeEmpty(Connection::class, [
            'createQueryBuilder' => $this->make(QueryBuilder::class, [
                'execute' => [$fileA, $fileB]
            ]),
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