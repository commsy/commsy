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

use App\Utils\DateHelper;
use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    public function testSameDayIsZero(): void
    {
        self::assertSame(0, DateHelper::daysBetween('20240101', '20240101'));
    }

    public function testConsecutiveDays(): void
    {
        self::assertSame(1, DateHelper::daysBetween('20240101', '20240102'));
    }

    public function testResultIsOrderIndependent(): void
    {
        self::assertSame(1, DateHelper::daysBetween('20240102', '20240101'));
    }

    public function testAcrossMonthBoundary(): void
    {
        self::assertSame(31, DateHelper::daysBetween('20240101', '20240201'));
    }

    public function testAcrossYearBoundary(): void
    {
        self::assertSame(1, DateHelper::daysBetween('20231231', '20240101'));
    }

    public function testLeapYearFebruary(): void
    {
        self::assertSame(2, DateHelper::daysBetween('20240228', '20240301'));
    }

    public function testNonLeapYearFebruary(): void
    {
        self::assertSame(1, DateHelper::daysBetween('20230228', '20230301'));
    }

    public function testFullLeapYear(): void
    {
        self::assertSame(365, DateHelper::daysBetween('20240101', '20241231'));
    }
}
