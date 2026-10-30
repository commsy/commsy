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

namespace Tests\Unit\Legacy;

use App\Legacy\SqlIdList;
use PHPUnit\Framework\TestCase;

final class SqlIdListTest extends TestCase
{
    public function testOnlyIntegersReachTheList(): void
    {
        self::assertSame('12, 0, 7, -1', SqlIdList::join(['12', '0) OR (1=1', 7, '-1']));
    }

    public function testTheSeparatorCanBeChosen(): void
    {
        self::assertSame('1,2', SqlIdList::join([1, 2], ','));
    }

    public function testAnEmptyListStaysEmpty(): void
    {
        self::assertSame('', SqlIdList::join([]));
    }
}
