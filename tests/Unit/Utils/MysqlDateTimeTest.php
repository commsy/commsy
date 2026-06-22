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

namespace Tests\Unit\Utils;

use App\Utils\MysqlDateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

class MysqlDateTimeTest extends TestCase
{
    use ClockSensitiveTrait;

    public function testNowFormatsAsMysqlDateTime(): void
    {
        static::mockTime(new \DateTimeImmutable('2024-03-15 14:30:45'));

        self::assertSame('2024-03-15 14:30:45', MysqlDateTime::now());
    }

    public function testTodayFormatsAsCompactYmd(): void
    {
        static::mockTime(new \DateTimeImmutable('2024-03-15 14:30:45'));

        self::assertSame('20240315', MysqlDateTime::today());
    }

    public function testResultsReflectTheMockedClock(): void
    {
        static::mockTime(new \DateTimeImmutable('2020-12-31 23:59:59'));

        self::assertSame('2020-12-31 23:59:59', MysqlDateTime::now());
        self::assertSame('20201231', MysqlDateTime::today());
    }

    public function testNowMinusDays(): void
    {
        static::mockTime(new \DateTimeImmutable('2024-03-15 10:30:00'));

        self::assertSame('2024-03-10 10:30:00', MysqlDateTime::nowMinusDays(5));
        // crosses the month boundary into the (leap-year) February
        self::assertSame('2024-02-29 10:30:00', MysqlDateTime::nowMinusDays(15));
    }

    public function testNowMinusMonths(): void
    {
        static::mockTime(new \DateTimeImmutable('2024-03-15 10:30:00'));

        self::assertSame('2024-01-15 10:30:00', MysqlDateTime::nowMinusMonths(2));
    }
}
