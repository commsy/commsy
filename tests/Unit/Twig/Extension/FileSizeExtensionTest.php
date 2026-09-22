<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Twig\Extension;

use App\Twig\Extension\FileSizeExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FileSizeExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{int, string}>
     */
    public static function sizes(): iterable
    {
        yield 'nothing' => [0, '0 B'];

        // The case that started this: an export document of a few hundred bytes
        // used to be shown as "0 KB".
        yield 'a small file keeps its unit' => [458, '458 B'];

        yield 'just short of a kilobyte' => [1023, '1023 B'];
        yield 'a kilobyte' => [1024, '1.0 KB'];
        yield 'kilobytes' => [1536, '1.5 KB'];
        yield 'megabytes' => [5 * 1024 * 1024, '5.0 MB'];
        yield 'gigabytes' => [3 * 1024 ** 3, '3.0 GB'];
    }

    #[DataProvider('sizes')]
    public function testSizesAreShownInTheUnitThatLeavesANumberWorthReading(int $bytes, string $expected): void
    {
        $this->assertSame($expected, (new FileSizeExtension())->format($bytes));
    }
}
