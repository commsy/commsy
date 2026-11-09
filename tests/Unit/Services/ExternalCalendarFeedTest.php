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

namespace Tests\Unit\Services;

use App\Services\ExternalCalendarFeed;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * The feed of an external calendar is fetched only from a public http(s)
 * address; webcal addresses count as http(s).
 */
final class ExternalCalendarFeedTest extends TestCase
{
    public function testAWebcalAddressIsFetchedOverHttp(): void
    {
        $requested = [];
        $client = new MockHttpClient(function (string $method, string $url) use (&$requested): MockResponse {
            $requested[] = $url;

            return new MockResponse('BEGIN:VCALENDAR', ['primary_ip' => '93.184.216.34']);
        });

        self::assertSame('BEGIN:VCALENDAR', (new ExternalCalendarFeed($client))->fetch('webcal://93.184.216.34/feed.ics'));
        self::assertSame(['http://93.184.216.34/feed.ics'], $requested);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function addressesOfOtherSchemes(): iterable
    {
        yield 'a local file' => ['file:///etc/hostname'];
        yield 'a php stream' => ['php://filter/resource=/etc/hostname'];
        yield 'no scheme' => ['/etc/hostname'];
    }

    #[DataProvider('addressesOfOtherSchemes')]
    public function testAnAddressOfAnotherSchemeIsNotFetched(string $url): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => self::fail('nothing may be fetched'));

        $this->expectException(InvalidArgumentException::class);
        (new ExternalCalendarFeed($client))->fetch($url);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function privateAddresses(): iterable
    {
        yield 'loopback' => ['http://127.0.0.1/feed.ics'];
        yield 'private network' => ['https://10.1.2.3/feed.ics'];
        yield 'link local' => ['http://169.254.169.254/latest/meta-data'];
    }

    #[DataProvider('privateAddresses')]
    public function testAnAddressInAPrivateNetworkIsNotFetched(string $url): void
    {
        $client = new MockHttpClient(new MockResponse('BEGIN:VCALENDAR'));

        $this->expectException(TransportExceptionInterface::class);
        (new ExternalCalendarFeed($client))->fetch($url);
    }
}
