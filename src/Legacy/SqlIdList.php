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

namespace App\Legacy;

/**
 * Joins ids for an unquoted IN (...) list of a raw legacy query. Every value
 * becomes an integer, so nothing but numbers reaches the query; escaping
 * alone does not protect an unquoted list.
 */
final class SqlIdList
{
    /**
     * @param iterable<mixed> $ids
     */
    public static function join(iterable $ids, string $separator = ', '): string
    {
        $integers = [];
        foreach ($ids as $id) {
            $integers[] = (int) $id;
        }

        return implode($separator, $integers);
    }
}
