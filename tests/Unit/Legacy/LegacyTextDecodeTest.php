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

use App\Legacy\LegacyTextDecode;
use PHPUnit\Framework\TestCase;

class LegacyTextDecodeTest extends TestCase
{
    public function testReplacesQuotEntityInString(): void
    {
        self::assertSame('say "hi"', LegacyTextDecode::fromFile('say &quot;hi&quot;'));
    }

    public function testRecursesIntoNestedArrays(): void
    {
        self::assertSame(
            ['a' => 'x"y', 'b' => ['c' => '"']],
            LegacyTextDecode::fromFile(['a' => 'x&quot;y', 'b' => ['c' => '&quot;']])
        );
    }

    public function testLeavesPlainStringsUntouched(): void
    {
        self::assertSame('plain text', LegacyTextDecode::fromFile('plain text'));
    }

    public function testEmptyValuesPassThrough(): void
    {
        self::assertSame('', LegacyTextDecode::fromFile(''));
        self::assertSame(['k' => ''], LegacyTextDecode::fromFile(['k' => '']));
    }
}
