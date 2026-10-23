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

namespace Tests\Unit\WOPI;

use App\WOPI\Discovery\DiscoveryCache;
use App\WOPI\Discovery\Response\NetZone;
use App\WOPI\Discovery\Response\WOPIDiscovery;
use App\WOPI\Discovery\Response\WOPIZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The discovery of the office server is kept between requests, whether its
 * address carries a path or not.
 */
final class DiscoveryCacheTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function addressProvider(): iterable
    {
        yield 'host only' => ['https://office-%s.test'];
        yield 'with a path' => ['https://office.test/%s/office'];
    }

    #[DataProvider('addressProvider')]
    public function testADiscoveryIsKeptForItsAddress(string $address): void
    {
        $baseUrl = sprintf($address, uniqid());
        $cache = new DiscoveryCache();
        $discovery = (new WOPIDiscovery())->setNetZones([
            (new NetZone())->setName(WOPIZone::EXTERNAL_HTTPS)->setApps([]),
        ]);

        try {
            self::assertTrue($cache->storeInCache($baseUrl, $discovery));
            self::assertEquals($discovery, $cache->getFromCache($baseUrl));
            self::assertNull($cache->getFromCache($baseUrl . '/other'));
        } finally {
            $cache->deleteFromCache($baseUrl);
        }

        self::assertNull($cache->getFromCache($baseUrl));
    }
}
