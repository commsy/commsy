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

    public function testConvertTimeFromInputParsesClockTime(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '143000', 'datetime' => '14:30:00', 'display' => ''],
            DateHelper::convertTimeFromInput('14:30')
        );
    }

    public function testConvertTimeFromInputBareHour(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '080000', 'datetime' => '08:00:00', 'display' => ''],
            DateHelper::convertTimeFromInput('8')
        );
    }

    public function testConvertTimeFromInputPmShiftsToTwentyFourHour(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '140000', 'datetime' => '14:00:00', 'display' => ''],
            DateHelper::convertTimeFromInput('2pm')
        );
    }

    public function testConvertTimeFromInputCumTempore(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '101500', 'datetime' => '10:15:00', 'display' => 'ct'],
            DateHelper::convertTimeFromInput('10ct')
        );
    }

    public function testConvertTimeFromInputSineTempore(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '090000', 'datetime' => '09:00:00', 'display' => 'st'],
            DateHelper::convertTimeFromInput('9st')
        );
    }

    public function testConvertTimeFromInputInvalid(): void
    {
        self::assertSame(
            ['conforms' => false, 'timestamp' => '000000', 'datetime' => '00:00:00', 'display' => 'xyz'],
            DateHelper::convertTimeFromInput('xyz')
        );
    }
}
