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

namespace App\Search\QueryConditions;

interface QueryConditionInterface
{
    public const BOOL_MUST = 'must';
    public const BOOL_SHOULD = 'should';

    /**
     * @return AbstractQuery[]
     */
    public function getConditions(): array;

    public function getOperator(): string;
}
