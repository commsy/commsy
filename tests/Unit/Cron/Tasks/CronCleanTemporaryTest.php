<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Cron\Tasks;

use App\Cron\Tasks\CronCleanTemporary;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The task sweeps folders that hold other people's files, so every test works
 * in a project directory of its own and never in the real one.
 */
class CronCleanTemporaryTest extends TestCase
{
    private string $projectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->projectDir = sys_get_temp_dir().'/commsy-clean-temp-'.getmypid().'-'.bin2hex(random_bytes(4));
        $this->filesystem->mkdir([$this->projectDir.'/var/temp', $this->projectDir.'/files/temp']);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDir);
    }

    public function testAnOldFileIsRemovedAndAYoungOneKept(): void
    {
        $this->file('files/temp/old.txt', hoursOld: 25);
        $this->file('files/temp/young.txt', hoursOld: 23);

        $this->sweep();

        $this->assertFileDoesNotExist($this->path('files/temp/old.txt'));
        $this->assertFileExists($this->path('files/temp/young.txt'));
    }

    /**
     * The page promises a download window, and the task runs only now and then:
     * an archive made shortly before a run must still be there after it.
     */
    public function testAnExportArchiveSurvivesWhileItIsYoungAndGoesOnceItIsOld(): void
    {
        $archive = 'files/temp/room_export/42/room-42-export-1.zip';
        $this->file($archive, hoursOld: 1);

        $this->sweep();
        $this->assertFileExists($this->path($archive), 'a fresh archive is not swept');

        $this->age($archive, hours: 25);
        $this->age('files/temp/room_export/42', hours: 25);
        $this->age('files/temp/room_export', hours: 25);

        $this->sweep();

        $this->assertFileDoesNotExist($this->path($archive));
        $this->assertDirectoryDoesNotExist($this->path('files/temp/room_export/42'), 'nothing is left of the room folder');
        $this->assertDirectoryDoesNotExist($this->path('files/temp/room_export'));
    }

    /**
     * The archiver makes the folder and writes the file afterwards. A sweep
     * that falls in between must not take the folder from under it.
     */
    public function testAnEmptyDirectoryThatWasJustMadeIsLeftAlone(): void
    {
        $this->filesystem->mkdir($this->path('files/temp/room_export/42'));

        $this->sweep();

        $this->assertDirectoryExists($this->path('files/temp/room_export/42'));
    }

    public function testAnOldDirectoryThatStillHoldsAYoungFileIsKept(): void
    {
        $this->file('files/temp/zip_export/1700000000/result.zip', hoursOld: 2);
        $this->age('files/temp/zip_export/1700000000', hours: 30);
        $this->age('files/temp/zip_export', hours: 30);

        $this->sweep();

        $this->assertFileExists($this->path('files/temp/zip_export/1700000000/result.zip'));
    }

    public function testAnOldEmptyDirectoryIsRemoved(): void
    {
        $this->filesystem->mkdir($this->path('var/temp/leftover'));
        $this->age('var/temp/leftover', hours: 30);

        $this->sweep();

        $this->assertDirectoryDoesNotExist($this->path('var/temp/leftover'));
    }

    public function testTheFoldersThemselvesStayAndAreMadeIfTheyWereNeverThere(): void
    {
        $this->filesystem->remove($this->path('var/temp'));

        $this->sweep();

        $this->assertDirectoryExists($this->path('var/temp'));
        $this->assertDirectoryExists($this->path('files/temp'));
    }

    /**
     * A placeholder keeps an otherwise empty folder in the repository. It is not
     * output of anything.
     */
    public function testAPlaceholderFileIsLeftAlone(): void
    {
        $this->file('files/temp/.gitkeep', hoursOld: 400);

        $this->sweep();

        $this->assertFileExists($this->path('files/temp/.gitkeep'));
    }

    /**
     * Only what lies in the folder is the task's to remove. A link that leads
     * out of it is not followed.
     */
    public function testALinkOutOfTheFolderIsNotFollowed(): void
    {
        $outside = $this->projectDir.'/outside';
        $this->filesystem->mkdir($outside);
        file_put_contents($outside.'/precious.txt', 'keep me');
        touch($outside.'/precious.txt', time() - 400 * 3600);

        symlink($outside, $this->path('files/temp/link'));

        $this->sweep();

        $this->assertFileExists($outside.'/precious.txt');
    }

    private function sweep(): void
    {
        (new CronCleanTemporary(new ParameterBag(['kernel.project_dir' => $this->projectDir])))
            ->run(null);
    }

    private function path(string $relative): string
    {
        return $this->projectDir.'/'.$relative;
    }

    private function file(string $relative, int $hoursOld): void
    {
        $this->filesystem->dumpFile($this->path($relative), 'content');
        $this->age($relative, $hoursOld);
    }

    /**
     * Setting a file's time does not change its folder's, so ageing deepest
     * first leaves each one as it was set.
     */
    private function age(string $relative, int $hours): void
    {
        touch($this->path($relative), time() - $hours * 3600);
    }
}
