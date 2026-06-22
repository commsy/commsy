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

use App\Utils\StringCase;
use PHPUnit\Framework\TestCase;

class StringCaseTest extends TestCase
{
    public function testToUpperAscii(): void
    {
        self::assertSame('HELLO WORLD', StringCase::toUpper('hello world'));
    }

    public function testToUpperGermanUmlauts(): void
    {
        self::assertSame('ÄÖÜ', StringCase::toUpper('äöü'));
        self::assertSame('ÜBER', StringCase::toUpper('über'));
    }

    public function testToUpperAccented(): void
    {
        self::assertSame('CAFÉ', StringCase::toUpper('café'));
    }

    public function testToUpperSharpSExpandsToSs(): void
    {
        self::assertSame('STRASSE', StringCase::toUpper('straße'));
    }

    public function testToUpperEmptyString(): void
    {
        self::assertSame('', StringCase::toUpper(''));
    }

    public function testToLowerAscii(): void
    {
        self::assertSame('hello world', StringCase::toLower('HELLO WORLD'));
    }

    public function testToLowerGermanUmlauts(): void
    {
        self::assertSame('äöü', StringCase::toLower('ÄÖÜ'));
        self::assertSame('über', StringCase::toLower('ÜBER'));
    }

    public function testToLowerAccented(): void
    {
        self::assertSame('café', StringCase::toLower('CAFÉ'));
    }

    public function testToLowerEmptyString(): void
    {
        self::assertSame('', StringCase::toLower(''));
    }

    public function testRoundTripPreservesAsciiAndUmlauts(): void
    {
        self::assertSame('möchten', StringCase::toLower(StringCase::toUpper('möchten')));
    }
}
