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

namespace Tests\Integration\Files\Cleanup;

use App\Command\FilesAuditCommand;
use App\Command\FilesCleanCommand;
use App\Command\FilesQuarantineCommand;
use App\Files\Cleanup\FileAudit;
use App\Files\Cleanup\FileCleaner;
use App\Files\Cleanup\FileQuarantine;
use App\Files\FileDeleter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * The three commands around the file cleanup: the audit and a dry run change
 * nothing, and what was moved can be listed and moved back.
 */
#[WithStory(RoomWithMemberStory::class)]
final class FilesCommandsTest extends KernelTestCase
{
    use BuildsFilesDirectory;
    use Factories;

    private FileAudit $fileAudit;
    private FileQuarantine $quarantine;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpFilesDirectory();

        $container = self::getContainer();
        $fileDeleter = $container->get(FileDeleter::class);
        $this->fileAudit = new FileAudit($container->get(EntityManagerInterface::class), $fileDeleter, $this->filesDirectory);
        $this->quarantine = new FileQuarantine($this->filesDirectory, new NullLogger());
    }

    protected function tearDown(): void
    {
        $this->tearDownFilesDirectory();
        parent::tearDown();
    }

    public function testTheAuditReportsAndWritesItsFindings(): void
    {
        $stray = $this->handedOutFileId();
        $this->file($this->roomDirectory()."/$stray.pdf");
        $csv = $this->filesDirectory->root().'/../findings-'.uniqid().'.csv';

        $tester = new CommandTester(new FilesAuditCommand($this->fileAudit));
        self::assertSame(Command::SUCCESS, $tester->execute(['--csv' => $csv]));

        self::assertStringContainsString('stray-upload', $tester->getDisplay());
        self::assertStringContainsString($this->roomDirectory()."/$stray.pdf", (string) file_get_contents($csv));
        unlink($csv);
    }

    public function testADryRunChangesNothing(): void
    {
        $stray = $this->file($this->roomDirectory().'/'.$this->handedOutFileId().'.pdf');
        $unlinked = $this->fileRow();

        $tester = new CommandTester($this->cleanCommand());
        self::assertSame(Command::SUCCESS, $tester->execute([]));

        self::assertStringContainsString('Dry run', $tester->getDisplay());
        self::assertFileExists($stray);
        self::assertNull($this->connection->fetchOne('SELECT deletion_date FROM files WHERE files_id = ?', [$unlinked]));
        self::assertSame([], $this->quarantine->runs());
    }

    public function testExecuteCleansAndTheQuarantineGivesItBack(): void
    {
        $stray = $this->file($this->roomDirectory().'/'.$this->handedOutFileId().'.pdf', 'stray content');

        $tester = new CommandTester($this->cleanCommand());
        self::assertSame(Command::SUCCESS, $tester->execute(['--execute' => true]));
        self::assertFileDoesNotExist($stray);

        $runs = $this->quarantine->runs();
        self::assertCount(1, $runs);

        $quarantine = new CommandTester(new FilesQuarantineCommand($this->quarantine));
        self::assertSame(Command::SUCCESS, $quarantine->execute([]));
        self::assertStringContainsString($runs[0]['name'], $quarantine->getDisplay());

        self::assertSame(Command::SUCCESS, $quarantine->execute(['action' => 'restore', 'run' => $runs[0]['name']]));
        self::assertFileDoesNotExist($stray, 'without --execute a restore only shows the run');

        self::assertSame(Command::SUCCESS, $quarantine->execute(['action' => 'restore', 'run' => $runs[0]['name'], '--execute' => true]));
        self::assertStringEqualsFile($stray, 'stray content');
        self::assertSame([], $this->quarantine->runs());
    }

    public function testAnUnknownKindIsRefused(): void
    {
        $tester = new CommandTester($this->cleanCommand());

        self::assertSame(Command::INVALID, $tester->execute(['--kind' => ['everything']]));
    }

    private function cleanCommand(): FilesCleanCommand
    {
        return new FilesCleanCommand(
            $this->fileAudit,
            new FileCleaner(
                self::getContainer()->get(FileDeleter::class),
                $this->quarantine,
                $this->filesDirectory,
                self::getContainer()->get(EntityManagerInterface::class),
                new NullLogger(),
            ),
        );
    }
}
