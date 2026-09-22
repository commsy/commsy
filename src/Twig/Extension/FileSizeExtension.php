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

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Renders a number of bytes in the largest unit that leaves a number worth
 * reading. Dividing by 1024 and rounding turns every small file into "0 KB".
 */
final class FileSizeExtension extends AbstractExtension
{
    private const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    public function getFilters(): array
    {
        return [
            new TwigFilter('file_size', $this->format(...)),
        ];
    }

    public function format(int $bytes): string
    {
        $size = max($bytes, 0);
        $unit = 0;

        while ($size >= 1024 && $unit < count(self::UNITS) - 1) {
            $size /= 1024;
            ++$unit;
        }

        // Bytes are whole; anything above carries one decimal, which is as
        // precise as a size in a list needs to be.
        return 0 === $unit
            ? sprintf('%d %s', $size, self::UNITS[$unit])
            : sprintf('%.1f %s', $size, self::UNITS[$unit]);
    }
}
