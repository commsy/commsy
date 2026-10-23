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

use App\Entity\User;
use App\Files\Cleanup\FileCleaner;
use App\Files\Cleanup\FileQuarantine;
use App\Files\Cleanup\FindingKind;
use App\Files\FileDeleter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * Pins {@see FileCleaner}: rows are only marked deleted, files without a row
 * only moved into the quarantine, and nothing happens where the audit looks
 * implausible.
 */
#[WithStory(RoomWithMemberStory::class)]
final class FileCleanerTest extends KernelTestCase
{
    use BuildsFilesDirectory;
    use Factories;

    private const DEFAULT_KINDS = [
        FindingKind::UnlinkedRow, FindingKind::DeadLinksRow, FindingKind::StrayUpload,
        FindingKind::StrayImage, FindingKind::OrphanedDirectory,
    ];

    private FileQuarantine $quarantine;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpFilesDirectory();
        $this->quarantine = new FileQuarantine($this->filesDirectory, new NullLogger());
    }

    protected function tearDown(): void
    {
        $this->tearDownFilesDirectory();
        parent::tearDown();
    }

    public function testRowsAreOnlyMarkedDeletedAndKeepTheirFiles(): void
    {
        $unlinked = $this->fileRow();
        $unlinkedPath = $this->file($this->roomDirectory()."/$unlinked.pdf");
        $deletedEntry = $this->entry();
        $this->connection->executeStatement('UPDATE items SET deletion_date = NOW() - INTERVAL 200 DAY WHERE item_id = ?', [$deletedEntry]);
        $dead = $this->fileRow();
        $this->link($dead, $deletedEntry);
        $kept = $this->fileRow();
        $this->link($kept, $this->entry());
        $this->file($this->roomDirectory()."/$kept.pdf");

        $outcome = $this->cleaner()->clean($this->audit(), self::DEFAULT_KINDS);

        self::assertSame(['unlinked-row' => 1, 'dead-links-row' => 1], $outcome['done']);
        self::assertNull($outcome['run'], 'rows need no quarantine');
        self::assertNotNull($this->rowDeletionDate($unlinked));
        self::assertNotNull($this->rowDeletionDate($dead));
        self::assertNotNull($this->connection->fetchOne('SELECT deletion_date FROM item_link_file WHERE file_id = ?', [$dead]));
        self::assertNull($this->rowDeletionDate($kept));
        self::assertFileExists($unlinkedPath, 'the final deletion removes the file after the retention');
    }

    public function testAFileMissingOnDiskEndsOnlyWhenNamed(): void
    {
        $missing = $this->fileRow();
        $this->link($missing, $this->entry());

        $this->cleaner()->clean($this->audit(), self::DEFAULT_KINDS);
        self::assertNull($this->rowDeletionDate($missing));

        $outcome = $this->cleaner()->clean($this->audit(), [FindingKind::MissingOnDisk]);
        self::assertSame(['missing-on-disk' => 1], $outcome['done']);
        self::assertNotNull($this->rowDeletionDate($missing));
        self::assertNotNull($this->connection->fetchOne('SELECT deletion_date FROM item_link_file WHERE file_id = ?', [$missing]));
    }

    public function testMissingFilesStayWhileTheStorageLooksUnmounted(): void
    {
        $rows = [];
        for ($i = 0; $i < 25; ++$i) {
            $rows[] = $row = $this->fileRow();
            $this->link($row, $this->entry());
        }

        $outcome = $this->cleaner()->clean($this->audit(), [FindingKind::MissingOnDisk]);
        self::assertArrayHasKey('missing-on-disk', $outcome['skipped']);
        self::assertNull($this->rowDeletionDate($rows[0]));

        $this->cleaner()->clean($this->audit(), [FindingKind::MissingOnDisk], ignoreWarnings: true);
        self::assertNotNull($this->rowDeletionDate($rows[0]));
    }

    public function testFilesWithoutRowGoIntoTheQuarantineAndOnlyThose(): void
    {
        $roomId = (int) $this->room->getItemId();
        $this->addExtras('room', 'item_id', $roomId, ['LOGOFILENAME' => "cid{$roomId}_logo_neu.png"]);
        $logo = $this->file($this->roomDirectory()."/cid{$roomId}_logo_neu.png");
        $this->file($this->roomDirectory()."/cid{$roomId}_logo_alt.png");
        $stray = $this->handedOutFileId();
        $this->file($this->roomDirectory()."/$stray.pdf");
        $unknown = $this->file($this->roomDirectory().'/notizen.txt');
        $orphan = "{$this->portalId}/{$this->goneContextId()}";
        $this->file("$orphan/1.pdf");
        $inSubdirectory = $this->file("$orphan/sub/x.txt");

        $outcome = $this->cleaner()->clean($this->audit(), self::DEFAULT_KINDS);

        self::assertSame(['stray-upload' => 1, 'stray-image' => 1, 'orphaned-directory' => 1], $outcome['done']);
        $moved = array_column($this->quarantine->manifest((string) $outcome['run']), 'path');
        sort($moved);
        $expected = [$this->roomDirectory()."/$stray.pdf", $this->roomDirectory()."/cid{$roomId}_logo_alt.png", "$orphan/1.pdf"];
        sort($expected);
        self::assertSame($expected, $moved);
        self::assertFileExists($logo);
        self::assertFileExists($unknown);
        self::assertFileExists($inSubdirectory, 'a subdirectory may belong to another context');
    }

    public function testAFileTouchedSinceTheAuditStays(): void
    {
        $stray = $this->handedOutFileId();
        $path = $this->file($this->roomDirectory()."/$stray.pdf");
        $audit = $this->audit();
        touch($path);

        $outcome = $this->cleaner()->clean($audit, [FindingKind::StrayUpload]);

        self::assertSame(['stray-upload' => 0], $outcome['done']);
        self::assertFileExists($path);
    }

    public function testAnUploadThatGotARowSinceTheAuditStays(): void
    {
        $stray = $this->handedOutFileId();
        $path = $this->file($this->roomDirectory()."/$stray.pdf");
        $audit = $this->audit();
        $this->connection->update('files', ['files_id' => $stray], ['files_id' => $this->fileRow(ageHours: 2)]);

        $outcome = $this->cleaner()->clean($audit, [FindingKind::StrayUpload]);

        self::assertSame(['stray-upload' => 0], $outcome['done']);
        self::assertFileExists($path);
    }

    public function testTheDiskStaysWhileTheDatabaseLooksWrong(): void
    {
        for ($i = 0; $i < 25; ++$i) {
            $this->file($this->roomDirectory().'/'.(900_000 + $i).'.pdf');
        }

        $outcome = $this->cleaner()->clean($this->audit(), [FindingKind::StrayUpload]);
        self::assertArrayHasKey('stray-upload', $outcome['skipped']);
        self::assertNull($outcome['run']);
        self::assertFileExists($this->filesDirectory->root().'/'.$this->roomDirectory().'/900000.pdf');

        $outcome = $this->cleaner()->clean($this->audit(), [FindingKind::StrayUpload], ignoreWarnings: true);
        self::assertSame(['stray-upload' => 25], $outcome['done']);
    }

    private function cleaner(): FileCleaner
    {
        return new FileCleaner(
            self::getContainer()->get(FileDeleter::class),
            $this->quarantine,
            $this->filesDirectory,
            self::getContainer()->get(EntityManagerInterface::class),
            new NullLogger(),
        );
    }

    private function entry(): int
    {
        /** @var User $user */
        $user = RoomWithMemberStory::get('roomUser');

        return (int) AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $user])->getItemId();
    }

    private function rowDeletionDate(int $fileId): ?string
    {
        $value = $this->connection->fetchOne('SELECT deletion_date FROM files WHERE files_id = ?', [$fileId]);

        return false === $value ? null : $value;
    }
}
