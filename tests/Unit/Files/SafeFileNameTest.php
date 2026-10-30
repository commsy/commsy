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

namespace Tests\Unit\Files;

use App\Files\SafeFileName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SafeFileNameTest extends TestCase
{
    #[DataProvider('names')]
    public function testANameBecomesOneSegment(string $name, string $segment): void
    {
        self::assertSame($segment, SafeFileName::of($name));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function names(): iterable
    {
        yield 'plain title' => ['Protokoll vom 3. Mai', 'Protokoll vom 3. Mai'];
        yield 'umlauts stay' => ['Übung für Größen', 'Übung für Größen'];
        yield 'climbing up' => ['../../../1/115_/9999', '_.._.._1_115__9999'];
        yield 'absolute path' => ['/etc/passwd', '_etc_passwd'];
        yield 'backslashes' => ['..\\..\\x', '_.._x'];
        yield 'control characters' => ["a\x00b\nc", 'a_b_c'];
        yield 'only dots' => ['..', 'entry'];
        yield 'hidden file' => ['.htaccess', 'htaccess'];
        yield 'empty' => ['   ', 'entry'];
    }

    public function testALongNameIsCutWithoutBreakingACharacter(): void
    {
        $name = SafeFileName::of(str_repeat('ä', 150));

        self::assertLessThanOrEqual(200, strlen($name));
        self::assertTrue(mb_check_encoding($name, 'UTF-8'));
    }
}
