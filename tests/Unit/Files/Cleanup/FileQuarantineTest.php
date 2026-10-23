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

namespace Tests\Unit\Files\Cleanup;

use App\Files\Cleanup\FileQuarantine;
use App\Files\FilesDirectory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Files the cleanup takes off the disk are moved, not deleted: they can be
 * moved back, and only an old run is removed for good.
 */
final class FileQuarantineTest extends TestCase
{
    private string $root;
    private FileQuarantine $quarantine;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/phpunit-quarantine-'.uniqid();
        (new Filesystem())->mkdir($this->root.'/101/98_');
        $this->quarantine = new FileQuarantine(new FilesDirectory($this->root, '/unused'), new NullLogger());
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->root);
    }

    public function testAMovedFileKeepsItsPathInsideTheRun(): void
    {
        file_put_contents($this->root.'/101/98_/7.pdf', 'content');

        $run = $this->quarantine->begin(new DateTimeImmutable('2026-10-23 12:00:00'));
        $this->quarantine->move($run, '101/98_/7.pdf', 'stray-upload: no file row');

        self::assertSame('20261023-120000', $run);
        self::assertFileDoesNotExist($this->root.'/101/98_/7.pdf');
        self::assertStringEqualsFile($this->root.'/_quarantine/20261023-120000/101/98_/7.pdf', 'content');
        self::assertSame(
            [['path' => '101/98_/7.pdf', 'reason' => 'stray-upload: no file row', 'directory' => false]],
            $this->quarantine->manifest($run),
        );
        self::assertSame([['name' => $run, 'entries' => 1, 'bytes' => 7]], $this->quarantine->runs());
    }

    public function testTwoRunsInTheSameSecondDoNotMix(): void
    {
        $now = new DateTimeImmutable('2026-10-23 12:00:00');

        self::assertSame('20261023-120000', $this->quarantine->begin($now));
        self::assertSame('20261023-120000-2', $this->quarantine->begin($now));
    }

    public function testNothingOutsideTheFilesDirectoryOrInTheQuarantineIsMoved(): void
    {
        $run = $this->quarantine->begin();
        file_put_contents(sys_get_temp_dir().'/phpunit-outside-'.basename($this->root), 'keep');
        symlink($this->root.'/101/98_', $this->root.'/101/link');

        foreach (['../phpunit-outside-'.basename($this->root), '_quarantine/'.$run, '101/link', '101/98_/missing.pdf', ''] as $path) {
            try {
                $this->quarantine->move($run, $path, 'test');
                self::fail("'$path' was moved");
            } catch (RuntimeException) {
                // expected
            }
        }

        self::assertFileExists(sys_get_temp_dir().'/phpunit-outside-'.basename($this->root));
        unlink(sys_get_temp_dir().'/phpunit-outside-'.basename($this->root));
        self::assertSame([], $this->quarantine->manifest($run));
    }

    public function testARunIsMovedBackAndThenGone(): void
    {
        file_put_contents($this->root.'/101/98_/7.pdf', 'content');
        (new Filesystem())->mkdir($this->root.'/101/1234_');
        file_put_contents($this->root.'/101/1234_/cid1234_logo_a.png', 'logo');
        $run = $this->quarantine->begin();
        $this->quarantine->move($run, '101/98_/7.pdf', 'test');
        $this->quarantine->move($run, '101/1234_', 'test');

        $outcome = $this->quarantine->restore($run);

        self::assertSame(['101/98_/7.pdf', '101/1234_'], $outcome['restored']);
        self::assertSame([], $outcome['conflicts']);
        self::assertStringEqualsFile($this->root.'/101/98_/7.pdf', 'content');
        self::assertStringEqualsFile($this->root.'/101/1234_/cid1234_logo_a.png', 'logo');
        self::assertSame([], $this->quarantine->runs());
    }

    public function testAPlaceTakenAgainIsNotOverwritten(): void
    {
        file_put_contents($this->root.'/101/98_/7.pdf', 'old');
        $run = $this->quarantine->begin();
        $this->quarantine->move($run, '101/98_/7.pdf', 'test');
        file_put_contents($this->root.'/101/98_/7.pdf', 'new');

        $outcome = $this->quarantine->restore($run);

        self::assertSame(['101/98_/7.pdf'], $outcome['conflicts']);
        self::assertStringEqualsFile($this->root.'/101/98_/7.pdf', 'new');
        self::assertStringEqualsFile($this->root."/_quarantine/$run/101/98_/7.pdf", 'old');
    }

    public function testANameThatIsNotUtf8IsMovedBackAsItWas(): void
    {
        $name = "101/98_/\xE4rger.pdf";
        file_put_contents($this->root.'/'.$name, 'latin1');
        $run = $this->quarantine->begin();
        $this->quarantine->move($run, $name, 'test');

        self::assertSame($name, $this->quarantine->manifest($run)[0]['path']);
        self::assertSame([$name], $this->quarantine->restore($run)['restored']);
        self::assertStringEqualsFile($this->root.'/'.$name, 'latin1');
    }

    public function testARunKeepsWhatItsManifestDoesNotList(): void
    {
        file_put_contents($this->root.'/101/98_/7.pdf', 'content');
        $run = $this->quarantine->begin();
        $this->quarantine->move($run, '101/98_/7.pdf', 'test');
        file_put_contents($this->root."/_quarantine/$run/unlisted.pdf", 'unlisted');

        self::assertSame(['101/98_/7.pdf'], $this->quarantine->restore($run)['restored']);
        self::assertStringEqualsFile($this->root."/_quarantine/$run/unlisted.pdf", 'unlisted');
    }

    public function testADamagedManifestIsReportedAndNothingIsMovedBack(): void
    {
        file_put_contents($this->root.'/101/98_/7.pdf', 'content');
        $run = $this->quarantine->begin();
        $this->quarantine->move($run, '101/98_/7.pdf', 'test');
        file_put_contents($this->root."/_quarantine/$run/manifest.jsonl", "{broken\n", FILE_APPEND);

        self::assertSame(-1, $this->quarantine->runs()[0]['entries']);
        try {
            $this->quarantine->restore($run);
            self::fail('a damaged manifest was used');
        } catch (RuntimeException) {
            // expected
        }
        self::assertFileExists($this->root."/_quarantine/$run/101/98_/7.pdf");
        self::assertFileDoesNotExist($this->root.'/101/98_/7.pdf');
    }

    public function testOnlyRunsOlderThanTheRetentionArePurged(): void
    {
        $now = new DateTimeImmutable('2026-10-23 12:00:00');
        $old = $this->quarantine->begin($now->modify('-91 days'));
        $recent = $this->quarantine->begin($now->modify('-89 days'));

        self::assertSame([$old], $this->quarantine->purge(FileQuarantine::RETENTION_DAYS, $now));
        self::assertDirectoryDoesNotExist($this->root."/_quarantine/$old");
        self::assertDirectoryExists($this->root."/_quarantine/$recent");
    }

    public function testAnUnknownRunIsRefused(): void
    {
        $this->expectException(RuntimeException::class);

        $this->quarantine->restore('../101');
    }
}
