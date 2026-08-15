<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Export;

use App\Export\RoomExportArchiver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class RoomExportArchiverTest extends TestCase
{
    private string $workingDirectory;

    protected function setUp(): void
    {
        $this->workingDirectory = sys_get_temp_dir().'/commsy-archiver-test-'.getmypid();
        (new Filesystem())->remove($this->workingDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->workingDirectory);
    }

    public function testItWritesTheDocumentIntoTheArchive(): void
    {
        $target = $this->workingDirectory.'/42/room-42-export-1.zip';

        $size = (new RoomExportArchiver())->write($target, ['formatVersion' => 1, 'room' => ['title' => 'Raum']]);

        $this->assertFileExists($target);
        $this->assertSame(filesize($target), $size);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($target));
        $contents = $zip->getFromName(RoomExportArchiver::DOCUMENT_NAME);
        $zip->close();

        $this->assertNotFalse($contents, 'the archive carries export.json');
        $this->assertSame(
            ['formatVersion' => 1, 'room' => ['title' => 'Raum']],
            json_decode((string) $contents, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testItCreatesTheTargetDirectory(): void
    {
        $target = $this->workingDirectory.'/deep/nested/room-1-export-1.zip';

        (new RoomExportArchiver())->write($target, []);

        $this->assertFileExists($target);
    }

    /**
     * A room title carries whatever someone typed. Escaped unicode would still
     * be correct JSON but makes the document needlessly hard to read for whoever
     * writes the importer.
     */
    public function testUmlautsStaySpelledOut(): void
    {
        $target = $this->workingDirectory.'/room-1-export-1.zip';

        (new RoomExportArchiver())->write($target, ['room' => ['title' => 'Übung & Größe']]);

        $zip = new ZipArchive();
        $zip->open($target);
        $contents = (string) $zip->getFromName(RoomExportArchiver::DOCUMENT_NAME);
        $zip->close();

        $this->assertStringContainsString('Übung & Größe', $contents);
    }

    /**
     * A write that cannot happen must not pass unnoticed — the handler turns a
     * raised failure into a visible one on the row.
     */
    public function testAnImpossibleTargetRaisesInsteadOfReturningQuietly(): void
    {
        // The parent cannot be created because a file already occupies its name.
        (new Filesystem())->dumpFile($this->workingDirectory.'/blocker', 'x');

        $this->expectException(IOException::class);

        (new RoomExportArchiver())->write($this->workingDirectory.'/blocker/nope.zip', []);
    }
}
