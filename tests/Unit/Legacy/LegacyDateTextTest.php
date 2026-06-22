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

namespace Tests\Unit\Legacy;

use App\Legacy\LegacyDateText;
use cs_translator;
use PHPUnit\Framework\TestCase;

class LegacyDateTextTest extends TestCase
{
    public function testConvertDateEuropeanNumeric(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '20240312', 'datetime' => '2024-03-12', 'display' => '', 'error' => false],
            LegacyDateText::convertDate('12.03.2024', 'de', $this->createMock(cs_translator::class))
        );
    }

    public function testConvertDateDbFormat(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '20240315', 'datetime' => '2024-03-15', 'display' => '', 'error' => false],
            LegacyDateText::convertDate('2024-03-15', 'de', $this->createMock(cs_translator::class))
        );
    }

    public function testConvertDateBritishNumeric(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '20240312', 'datetime' => '2024-03-12', 'display' => '', 'error' => false],
            LegacyDateText::convertDate('03/12/2024', 'en', $this->createMock(cs_translator::class))
        );
    }

    public function testMonthNameToInt(): void
    {
        // translator echoes the message key, so the input below is that key
        $translator = $this->createMock(cs_translator::class);
        $translator->method('getMessage')->willReturnArgument(0);

        self::assertSame('03', LegacyDateText::monthNameToInt('COMMON_DATE_MARCH_SHORT', $translator));
        self::assertSame('12', LegacyDateText::monthNameToInt('COMMON_DATE_DECEMBER_LONG', $translator));
        self::assertSame('unknown', LegacyDateText::monthNameToInt('unknown', $translator));
    }

    public function testWeekdayName(): void
    {
        $translator = $this->createMock(cs_translator::class);
        $translator->method('getMessage')->willReturnArgument(0);

        self::assertSame('COMMON_DATE_SUNDAY', LegacyDateText::weekdayName('0', $translator));
        self::assertSame('COMMON_DATE_SATURDAY', LegacyDateText::weekdayName('6', $translator));
        self::assertSame('', LegacyDateText::weekdayName('9', $translator));
    }
}
