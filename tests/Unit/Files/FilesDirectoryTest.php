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

namespace Tests\Unit\Files;

use App\Files\FilesDirectory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the directory layout for contexts of every length — the old cleanup
 * only knew the one of five to eight digits — and the names of uploads.
 */
final class FilesDirectoryTest extends TestCase
{
    private FilesDirectory $filesDirectory;

    protected function setUp(): void
    {
        $this->filesDirectory = new FilesDirectory('/srv/app/files/', '/srv/app');
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function contextProvider(): iterable
    {
        yield 'two digits' => [98, '98_'];
        yield 'four digits' => [1234, '1234_'];
        yield 'five digits' => [12345, '1234/5_'];
        yield 'seven digits' => [1234567, '1234/567_'];
        yield 'eight digits' => [12345678, '1234/5678_'];
        yield 'nine digits' => [123456789, '1234/5678/9_'];
    }

    #[DataProvider('contextProvider')]
    public function testAContextDirectoryIsNamedAsTheDiscManagerDoes(int $contextId, string $directory): void
    {
        self::assertSame("/srv/app/files/101/$directory", $this->filesDirectory->contextDirectory(101, $contextId));
        self::assertSame(['portalId' => 101, 'contextId' => $contextId], $this->filesDirectory->parseContextDirectory("101/$directory"));
    }

    #[DataProvider('contextProvider')]
    public function testTheSegmentIsWhatTheDiscManagerAndTheRoomServiceUse(int $contextId, string $directory): void
    {
        self::assertSame($directory, FilesDirectory::contextSegment($contextId));
        self::assertSame($directory, FilesDirectory::contextSegment((string) $contextId));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function extensionProvider(): iterable
    {
        yield 'lower case' => ['Bericht.pdf', 'pdf'];
        yield 'upper case' => ['Bericht.PDF', 'pdf'];
        yield 'last dot counts' => ['Bericht.final.docx', 'docx'];
        yield 'url encoded' => ['Bericht%20final.Pdf', 'pdf'];
        yield 'no dot' => ['README', ''];
    }

    #[DataProvider('extensionProvider')]
    public function testTheExtensionIsTakenAsTheDiscManagerNamesFiles(string $filename, string $extension): void
    {
        self::assertSame($extension, FilesDirectory::extensionOf($filename));
        self::assertSame("42.$extension", FilesDirectory::diskFilename(42, $extension));
    }

    public function testTheUnsplitLegacyDirectoryIsRecognised(): void
    {
        self::assertSame(['portalId' => 101, 'contextId' => 1234567], $this->filesDirectory->parseContextDirectory('101/1234567'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function noContextProvider(): iterable
    {
        yield 'portal only' => ['101'];
        yield 'not a portal' => ['portal/logos'];
        yield 'chunk without mark' => ['101/1234/567'];
        yield 'short inner chunk' => ['101/123/4_'];
        yield 'long last chunk' => ['101/1234/56789_'];
        yield 'letters' => ['101/abc_'];
        yield 'mark only' => ['101/_'];
    }

    #[DataProvider('noContextProvider')]
    public function testOtherDirectoriesAreNoContextDirectories(string $directory): void
    {
        self::assertNull($this->filesDirectory->parseContextDirectory($directory));
    }

    public function testPathsAreTakenRelativeToTheRootOnlyInsideIt(): void
    {
        self::assertSame('101/98_/1.pdf', $this->filesDirectory->relativeOf('/srv/app/files/101/98_/1.pdf'));
        self::assertNull($this->filesDirectory->relativeOf('/srv/app/files'));
        self::assertNull($this->filesDirectory->relativeOf('/srv/app/files/../config/x'));
        self::assertNull($this->filesDirectory->relativeOf('/srv/other/1.pdf'));
    }

    public function testAFilepathIsResolvedAgainstTheProjectDirectory(): void
    {
        self::assertSame('/srv/app/files/101/98_/1.pdf', $this->filesDirectory->absoluteOf('files/101/98_/1.pdf'));
        self::assertSame('/srv/app/elsewhere/1.pdf', $this->filesDirectory->absoluteOf('/elsewhere/1.pdf'));
    }
}
