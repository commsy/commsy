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

use App\Legacy\LegacyTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class LegacyDateTextTest extends TestCase
{
    public function testConvertDateEuropeanNumeric(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '20240312', 'datetime' => '2024-03-12', 'display' => '', 'error' => false],
            \App\Legacy\LegacyDateText::convertDate('12.03.2024', 'de', $this->translator())
        );
    }

    public function testConvertDateDbFormat(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '20240315', 'datetime' => '2024-03-15', 'display' => '', 'error' => false],
            \App\Legacy\LegacyDateText::convertDate('2024-03-15', 'de', $this->translator())
        );
    }

    public function testConvertDateBritishNumeric(): void
    {
        self::assertSame(
            ['conforms' => true, 'timestamp' => '20240312', 'datetime' => '2024-03-12', 'display' => '', 'error' => false],
            \App\Legacy\LegacyDateText::convertDate('03/12/2024', 'en', $this->translator())
        );
    }

    public function testMonthNameToInt(): void
    {
        // the translator echoes the message key, so the input below is that key
        $translator = $this->translator();

        self::assertSame('03', \App\Legacy\LegacyDateText::monthNameToInt('COMMON_DATE_MARCH_SHORT', $translator));
        self::assertSame('12', \App\Legacy\LegacyDateText::monthNameToInt('COMMON_DATE_DECEMBER_LONG', $translator));
        self::assertSame('unknown', \App\Legacy\LegacyDateText::monthNameToInt('unknown', $translator));
    }

    public function testWeekdayName(): void
    {
        $translator = $this->translator();

        self::assertSame('COMMON_DATE_SUNDAY', \App\Legacy\LegacyDateText::weekdayName('0', $translator));
        self::assertSame('COMMON_DATE_SATURDAY', \App\Legacy\LegacyDateText::weekdayName('6', $translator));
        self::assertSame('', \App\Legacy\LegacyDateText::weekdayName('9', $translator));
    }

    /**
     * A real LegacyTranslator (final, so not mockable) wrapping a stubbed Symfony translator
     * that echoes the message key back — keeps the date-name assertions key-based.
     */
    private function translator(): LegacyTranslator
    {
        $symfonyTranslator = $this->createMock(TranslatorInterface::class);
        $symfonyTranslator->method('trans')->willReturnArgument(0);

        return new LegacyTranslator($symfonyTranslator);
    }
}
