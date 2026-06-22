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

use App\Legacy\SqlStringEscaper;
use PHPUnit\Framework\TestCase;

class SqlStringEscaperTest extends TestCase
{
    public function testEscapesSingleQuote(): void
    {
        self::assertSame("O\\'Brien", SqlStringEscaper::escape("O'Brien"));
    }

    public function testEscapesBackslashAndDoubleQuote(): void
    {
        self::assertSame('a\\\\b\\"c', SqlStringEscaper::escape('a\\b"c'));
    }

    public function testEscapesControlCharacters(): void
    {
        self::assertSame('line1\\nline2\\r\\0\\Z', SqlStringEscaper::escape("line1\nline2\r\0\x1a"));
    }

    public function testEscapesArrayRecursively(): void
    {
        self::assertSame(
            ["O\\'Brien", ['x' => 'a\\"b']],
            SqlStringEscaper::escape(["O'Brien", ['x' => 'a"b']])
        );
    }

    public function testNonStringValuesPassThroughUnchanged(): void
    {
        self::assertSame(42, SqlStringEscaper::escape(42));
        self::assertNull(SqlStringEscaper::escape(null));
    }

    public function testEmptyStringPassesThroughUnchanged(): void
    {
        self::assertSame('', SqlStringEscaper::escape(''));
    }
}
