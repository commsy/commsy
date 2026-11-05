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

namespace Tests\Unit\Cron\Tasks;

use App\Cron\Tasks\CronPruneCaches;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * The daily run removes the expired entries of a cache on the filesystem,
 * which the cache would keep for good if no one read them again, and keeps
 * the valid ones. A cache that cannot be pruned is left alone.
 */
final class CronPruneCachesTest extends TestCase
{
    private string $directory;

    public function testExpiredEntriesAreRemovedAndValidOnesKept(): void
    {
        $pool = new FilesystemAdapter('', 0, $this->directory);
        $pool->save($pool->getItem('expired')->set('x')->expiresAfter(1));
        $pool->save($pool->getItem('valid')->set('y')->expiresAfter(3600));
        self::assertSame(2, $this->files());
        sleep(2);

        (new CronPruneCaches($pool, new ArrayAdapter()))->run(null);

        self::assertSame(1, $this->files());
        self::assertTrue($pool->getItem('valid')->isHit());
    }

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/commsy-prune-test-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    private function files(): int
    {
        return (new Finder())->files()->in($this->directory)->count();
    }
}
