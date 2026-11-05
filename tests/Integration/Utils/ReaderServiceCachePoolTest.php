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

namespace Tests\Integration\Utils;

use App\Utils\ReaderService;
use cs_item;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * The read status is cached in the configured pool, which the daily pruning
 * reaches, and not in a cache of its own in the temporary folder.
 */
final class ReaderServiceCachePoolTest extends KernelTestCase
{
    public function testTheReadStatusOfAnEntryIsDroppedFromTheConfiguredPool(): void
    {
        self::bootKernel();
        /** @var TagAwareCacheInterface $pool */
        $pool = self::getContainer()->get('read_status.cache');
        $pool->delete('probe');
        $pool->get('probe', static function (ItemInterface $item): string {
            $item->tag(['item_material_4711']);

            return 'cached';
        });

        $entry = $this->createStub(cs_item::class);
        $entry->method('getItemId')->willReturn(4711);
        $entry->method('getItemType')->willReturn('material');
        self::getContainer()->get(ReaderService::class)->invalidateCachedReadStatusForItem($entry);

        self::assertSame('computed', $pool->get('probe', static fn (): string => 'computed'));
    }
}
