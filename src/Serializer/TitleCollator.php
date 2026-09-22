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

namespace App\Serializer;

use Collator;

/**
 * Orders titles for output, by collation rather than by bytes: a byte
 * comparison files "Übung" behind "Zebra".
 *
 * The locale is fixed rather than following the room's language, and which one
 * barely matters: German, English and the CLDR root all sort an accent with its
 * base letter and agree on mixed titles. It would matter for a language that
 * gives umlauts their own place after z, such as Swedish. German is named as
 * the language most of this content is written in.
 */
final readonly class TitleCollator
{
    private Collator $collator;

    public function __construct()
    {
        $this->collator = new Collator('de');
    }

    public function compare(string $a, string $b): int
    {
        $result = $this->collator->compare($a, $b);

        return false === $result ? 0 : $result;
    }
}
